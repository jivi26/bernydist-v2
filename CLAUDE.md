# CLAUDE.md — BernyDist v2

Migración de `bernydist` (PHP 7.4 + CodeIgniter 2 + MariaDB) a **Laravel 13 + Vue 3 + Vite**.
El schema completo, inventario de código y riesgos están en [MIGRATION.md](MIGRATION.md).

---

## Entorno de desarrollo

```powershell
# SIEMPRE usar el PHP de Laragon — el sistema resuelve a XAMPP 7.3
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"

# Servidor Laravel
& $php artisan serve --port=8000

# Frontend (en otra terminal)
npm run dev

# Migraciones
& $php artisan migrate
```

**Base de datos:** Hostinger MariaDB 11.8.6 — copia de producción (safe para experimentar)
- Host: `srv1447.hstgr.io` · DB: `u658102838_test` · Credenciales en `.env`

**Legacy:** Código original en `C:\xampp\htdocs\bernydist` — autorizado para leer, NO modificar.

---

## Restricciones críticas

### ERP sync (LEER ANTES DE TOCAR TABLAS LEGACY)
Las tablas `_CLIENTES`, `_DIRS_CLIENTES`, `_BR_EMAILS_CLIENTES`, `_PRECIOS_ARTICULOS` y **todas las `_*`** se sincronizan cada noche desde Firebird (ERP). Son **read-only desde Laravel**:
- ❌ No agregar columnas a tablas `_*`
- ❌ No hashear `_DIRS_CLIENTES.PASS` — el ERP lo sobreescribe cada noche
- ❌ No confiar en PKs auto-increment — vienen del ERP
- ✅ Solo leer con Eloquent; escribir solo en tablas nuevas creadas via migrations

### Tablas bi_*
Son de un proyecto de migración anterior **abandonado**. Ignorar completamente. No referenciar en ningún modelo, migración o query.

### Contraseñas
`_DIRS_CLIENTES.PASS` está en texto plano — el ERP es el dueño. `validateCredentials()` siempre compara texto plano. Nunca llamar `Hash::check()` ni `Hash::make()` para credenciales de clientes.

---

## Arquitectura implementada

### Auth
- **`LegacyUserProvider`** (`app/Auth/`) — provider custom registrado en `AppServiceProvider`
- Tabla `users`: solo `(id, cliente_id, remember_token)` — sin contraseña
- Login acepta email (`_BR_EMAILS_CLIENTES`) o `CLAVE_CLIENTE` (`_CLIENTES`)
- Password siempre contra `_DIRS_CLIENTES.PASS WHERE ES_DIR_PPAL='S'` en texto plano

### Modelos
```
app/Models/
├── User.php                    ← tabla 'users' (auth bridge)
└── Legacy/                     ← modelos read-only sobre tablas legacy
    ├── Cliente.php             (_CLIENTES — PK: CLIENTE_ID, no auto-increment)
    ├── DireccionCliente.php    (_DIRS_CLIENTES — PASS está en $hidden)
    ├── Product.php             (products — price/iva son float, usar _PRECIOS_ARTICULOS)
    ├── Category.php            (categories)
    ├── Group.php               (groups)
    ├── Division.php            (divisions)
    ├── ProductBrand.php        (brands)
    ├── PrecioArticulo.php      (_PRECIOS_ARTICULOS)
    ├── PrecioEmpresa.php       (_PRECIOS_EMPRESA)
    ├── PedidoWeb.php           (pedidos_web2 — ESTATUS cast a OrderStatus enum)
    └── PedidoWebDetalle.php    (pedidos_web_detalle)
```

### Enums
```
app/Enums/
├── OrderStatus.php    ← '0','1','2','4','5','11' (varchar en BD)
├── Brand.php          ← BR, TL, GM, EQ
└── PriceList.php      ← IDs: 42, 43, 47, 58937, 102889139, 103582441
```

### Frontend
- **Vue 3** + **Vite** + **Tailwind CSS v4** + **Vue Router** (SPA) — ya configurados
- Entry point: `resources/js/app.js` → monta `App.vue` → `<RouterView>`
- Layout base: `resources/views/layouts/app.blade.php` — **usar `@php` block antes de `@json`** para evitar ParseError con operadores nullsafe (`?->`)
- Shell Blade: `resources/views/spa.blade.php` — vista mínima que sirve el SPA
- Alias `@` → `resources/js`
- Build: `npm run build` | Dev: `npm run dev`
- URL de desarrollo: `http://localhost:8000` (via `artisan serve --port=8000`)

#### Estructura Vue
```
resources/js/
├── app.js                  ← entry point, monta App.vue, registra router
├── components/App.vue      ← root: solo <RouterView />
├── router/index.js         ← Vue Router con createWebHistory()
├── pages/
│   ├── LandingPage.vue     ← / (selector de 4 marcas)
│   └── StorefrontPage.vue  ← /mayoreo (header + hero + categorías + footer)
└── layout/
    ├── AppHeader.vue       ← logo, búsqueda, carrito, login/dropdown usuario
    └── AppFooter.vue       ← columnas nav, redes, contacto, copyright
```

#### Iconos
- Paquete: **`@heroicons/vue/24/outline`** — SVG limpios, tree-shakeable
- **Nunca usar emojis como íconos** (🚚, 💳, etc.) — siempre Heroicons
- `RouterLink` y `RouterView` son globales (via `app.use(router)`) — no importar en `<script setup>`

#### Paleta de colores (extraída del sitio original)
Definidas en `resources/css/app.css` bajo `@theme`:
```
--color-brand-yellow: #ffd500   /* amarillo primario */
--color-brand-amber:  #f2b02c   /* ámbar — highlights, nav activo */
--color-brand-orange: #f37e2b   /* naranja — botones CTA */
--color-brand-dark:   #141414   /* header y navbar */
--color-brand-dark2:  #161616   /* fondo oscuro secundario */
--color-brand-text:   #222222   /* texto principal */
--color-brand-muted:  #777777   /* texto secundario */
```
Usar valores hex directos en clases Tailwind: `bg-[#141414]`, `text-[#f2b02c]`, etc.

#### Rutas web (SPA catch-all)
```php
// web.php — {any?} es opcional para capturar también GET /
Route::get('/{any?}', fn() => view('spa'))->where('any', '^(?!api|login|logout).*$');
```

---

## Multi-marca y multi-lista de precios

El sistema maneja múltiples marcas y listas de precio. Usar los Enums:

```php
Brand::BernyDist->productsTable()   // 'products'
Brand::Equimaq->productsTable()     // 'products_eqm'

PriceList::Lista->dbView()          // 'products_preciolista' (PRECIO_EMPRESA_ID = 42)
PriceList::GomaFacilVerde->dbView() // 'products_gmverde'
```

La marca activa se detecta por URL/dominio en middleware `ResolveBrand` (pendiente de implementar).

---

## Stored Procedures de pricing (NO reescribir aún)

9 SPs en MariaDB manejan descuentos y precios. Llamar via `DB::select()`:
```php
DB::select('CALL getDesctoGralArt(?, ?, ?, ?)', [...]);
```
Los SPs se reescriben en PHP solo cuando haya tests que validen que los resultados son idénticos.

---

## Estado de fases

| Fase | Descripción | Estado |
|------|-------------|--------|
| 1 | Cimientos: auth, modelos, Vue 3 | ✅ Completa |
| 2a | Storefront shell: landing, /mayoreo, header, footer | ✅ Completa |
| 2b | Catálogo: productos, categorías, búsqueda, detalle | 🔄 Siguiente |
| 3 | Carrito + Precios (via SPs) | ⬜ Pendiente |
| 4 | Checkout + Pagos (Stripe + Conekta) | ⬜ Pendiente |
| 5 | Panel del cliente (port de spages.php) | ⬜ Pendiente |
| 6 | API móvil vendedores (/api/v1/vendors/) | ⬜ Pendiente |
| 7 | Cutover a producción (berny.mx) | ⬜ Pendiente |

---

## Convenciones

- Controladores de API bajo `app/Http/Controllers/Api/V1/`
- Rutas: `web.php` (público + cliente), `admin.php` (panel interno), `api.php` (móvil)
- Modelos legacy: `$timestamps = false`, `$incrementing = false`, `$guarded = ['PK']`
- Sin comentarios obvios — solo cuando el WHY no es evidente
- No modificar tablas legacy ni crear migraciones que las alteren
