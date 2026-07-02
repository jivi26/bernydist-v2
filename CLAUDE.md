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
- **`LoginController`** (`app/Http/Controllers/Auth/`) — POST `/login`, redirige con `intended()`; tras `Auth::attempt()` exitoso llama `UserSessionService::initSession()`
- **`ForgotPasswordController`** (`app/Http/Controllers/Auth/`) — POST `/recuperar-password`; busca cliente por email o CLAVE_CLIENTE, notifica a `config('berny.emails.ppal')` y devuelve mensaje genérico (no revela si el usuario existe). Reset real se implementa en Fase 5.

### Sesiones (equivalente a `sessionAuth()` del legacy)

- **`UserSessionService`** (`app/Services/`) — replica la función `sessionAuth()` del legacy (`pages.php:1533`). Se llama tras `Auth::attempt()` exitoso y via `EnsureUserSession` middleware.
  - Escribe `session('user')` y `session('cart')` con las mismas claves que el objeto legacy.
  - Consulta: `_CLIENTES`, `_TIPOS_CLIENTES`, `_BR_EMAILS_CLIENTES`, `_vendedor`, `_DIRS_CLIENTES` (JOIN `_LOCALIDADES`, `_CIUDADES`, `_ESTADOS`, `_PAISES`)
  - **Columnas críticas de `_CLIENTES`**: `ASESOR_LOGIN_ID` y `TIPO_LOCALIDAD` **NO existen** en esa tabla. `ASESOR_LOGIN_ID` lo asigna el ACL de staff (siempre `null` para clientes normales). `TIPO_LOCALIDAD` viene de `_LOCALIDADES` via JOIN en `_DIRS_CLIENTES.LOCALIDAD_ID`.
  - Fases: 2=datos esenciales ✅ · 3=políticas descuento reales (SPs) · 4=keys pasarela pago

- **`EnsureUserSession`** (`app/Http/Middleware/`) — registrado como middleware global (`bootstrap/app.php`). Si `Auth::check()=true` pero `session('user')` no existe (remember_token re-auth / sesión expirada), re-popula la sesión llamando `initSession()`.

**Claves de `session('user')` disponibles:**
```
CLIENTE_ID, CLAVE_CLIENTE, NOMBRE, EMAIL, email_default,
TIPO_CLIENTE_ID, CONTADO, SOLO_VTA_CONTADO, is_public,
client_type (WG/WD/CR), client_subtype, user_type,
cash_sale, switched, ASESOR_LOGIN_ID (null para clientes),
OPERADOR_TMK, OPERADOR_TMK_ID, EMAILAGENT,
TIPO_LOCALIDAD (de _LOCALIDADES), PCTJ_PRONTOPAGO,
PERMITIR_CLTE_RECOGE, discount, price_level_reached,
discount_range, main_address (array con geo),
distrib_label, distrib_url
```

**`session('cart')` inicializado como:**
```php
['items' => [], 'total' => 0, 'shipping' => 0, 'shippingTax' => 0,
 'location_id' => null, 'positive_balance' => 0, 'discount_reached' => 0]
```

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
├── app.js                      ← entry point, monta App.vue, registra router
├── components/App.vue          ← root: solo <RouterView />
├── router/index.js             ← Vue Router con createWebHistory()
├── pages/
│   ├── LandingPage.vue         ← / (selector de 4 marcas)
│   ├── StorefrontPage.vue      ← /mayoreo (carrusel 6 slides: slide 0=hero-texto + slides 1-5=banners, categorías, footer)
│   ├── LoginPage.vue           ← /login — formulario único (name="code" acepta email o clave_cliente + password). Errores y old inputs desde window.appData.
│   ├── ForgotPasswordPage.vue  ← /recuperar-password — input email/clave + botón "Reestablecer". Flash success desde window.appData.flash.
│   ├── CatalogPage.vue         ← /catalogo — dos modos (ver § Catálogo abajo)
│   └── ProductDetailPage.vue   ← /catalogo/:id — detalle de producto con galería
└── layout/
    ├── AppHeader.vue           ← (ver detalle abajo)
    └── AppFooter.vue           ← columnas nav, redes, contacto, copyright. Logo: logo-black.png
```

#### AppHeader — comportamiento y layout

Logo: `/images/logo-black.png` (copiado de `bernydist/assets/img/`). Fondo completo `#141414`.

**Barra superior** (siempre visible):
- Izq: `[Hamburger*] [Logo]`
- Centro: `[Buscador flex-1]` (dark: `bg-[#222222]`, `border-gray-600`)
- Der: `[Icono búsqueda móvil] [Carrito] [PieChart**] [Bell***] [Dropdown usuario]`

`*` Hamburger visible cuando `scrolled || !isLargeScreen` (scroll > 60px o ancho < 1024px).
`**` `ChartPieIcon` — solo si `user.solo_vta_contado === 'S'`. Badge amarillo con `price_level_reached`. Enlaza a `/panel/pedido`.
`***` `BellIcon` — solo autenticados. Badge rojo con total de notificaciones. Amarillo/animado cuando `notifTotal > 0`. Dropdown con desglose de pedidos estatus 0/4/5.
El botón "Iniciar sesión" NO está en la barra superior — está en el nav bar.

**Cintilla de navegación** (solo desktop, desaparece al hacer scroll):
- `Inicio | Nosotros | Promociones* | Mi cuenta* | Divisiones** | [spacer] | [Video***] | [Catálogo****] | [Distribuidor] | [Iniciar sesión / Salir]`
- `*` solo autenticados · `**` solo asesores · `***` si hay video configurado · `****` solo `tipo_cliente_id` 88721 (WD) o 187185 (CR) sin `asesor_login_id`
- "Iniciar sesión" = botón naranja redondeado con `ArrowRightOnRectangleIcon`
- Controlada por `v-if="showNavBar"` con `<Transition>` (fade + slide sutil)

**Menú desplegable** (hamburger):
- Usado en móvil siempre y en desktop cuando hay scroll
- `mobileMenuOpen` booleano — se cierra automáticamente cuando `showNavBar` vuelve a ser `true`
- Incluye: Inicio, Nosotros, Promociones*, Mi cuenta*, Divisiones**, Catálogo****, Distribuidor, Ingresar/Salir

**Lógica JS:**
```js
scrolled      = window.scrollY > 60          // listener pasivo en scroll
isLargeScreen = window.innerWidth >= 1024    // listener pasivo en resize
showNavBar    = !scrolled && isLargeScreen
showHamburger = scrolled || !isLargeScreen
showCatalogo  = user && (tipo_cliente_id === '88721' || '187185') && !asesor_login_id
notifTotal    = window.appData.notifications.total   // calculado en Blade
// watch(showNavBar): cierra mobileMenuOpen cuando nav bar reaparece
// Listeners limpios en onBeforeUnmount
// handleClickOutside: cierra userMenuOpen Y bellOpen
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

#### Catálogo — CatalogPage y API

**Rutas Vue Router:**
```js
{ path: '/catalogo',          name: 'catalogo', component: CatalogPage }
{ path: '/catalogo/:id(\\d+)', name: 'producto', component: ProductDetailPage }
```

**Rutas API** (`routes/api.php`):
```
GET  /api/catalogo          → CatalogController@products   (paginado 24, con precio e imagen)
GET  /api/catalogo/{id}     → CatalogController@show       (detalle + todas las imágenes)
GET  /api/categorias        → CatalogController@categories  (con ?division= opcional)
GET  /api/divisiones        → CatalogController@divisions
```

**`CatalogController`** (`app/Http/Controllers/Api/CatalogController.php`):
- `ROL_IMAGEN_PRINCIPAL = 104183870` — constante para imagen principal de producto
- CDN productos: `https://berny.mx/uploads/products/400/{IMAGEN_ARTICULO_ID}.webp`
- CDN categorías: `https://www.berny.mx/uploads/categoriesweb/{category_id}.webp`
- `categories()` usa `whereExists` + subquery count para filtrar categorías con productos BR activos. **NO filtra por `categories.brand`** — ese campo usa nombres completos (AKSI, TECNOLITE, etc.), no 'BR'. El filtro correcto es `products.TIPO_ARTICULO_VTA = 'BR'`.
- `divisions()` filtra por join con products (`TIPO_ARTICULO_VTA='BR'`), no por `categories.brand`.
- `extractKeywords()` — replica el algoritmo legacy: elimina diacríticos → stop words → sufijos plurales → keywords individuales para búsqueda OR.
- Ordenamiento por relevancia: cuando hay 2+ keywords, usa `orderByRaw` con score CASE WHEN por keyword (1 punto por campo que coincide). Más keywords coincidentes = primero. Desempate: `orden_catalogo`.

**`CatalogPage.vue` — dos modos según URL:**

| URL | Modo | Contenido principal |
|-----|------|---------------------|
| `/catalogo` o `?division=X` | Categorías | Grid de cards con imagen de categoría + nombre + conteo (24 por página, client-side) |
| `?categoria=X` o `?q=texto` | Productos | Grid de productos con sidebar de filtros |

- Sidebar siempre visible en ambos modos (Divisiones + Categorías acordeones)
- Seleccionar división → permanece en modo categorías, filtra el grid
- Seleccionar categoría (sidebar o card) → cambia a modo productos
- Breadcrumb "← Catálogo" en modo productos vuelve al grid de categorías
- Paginación de categorías: client-side (24/página), estado `categoryPage` separado de `currentPage`
- Búsqueda desde AppHeader (`?q=`) activa modo productos directamente

**Campos importantes:**
- `scopeActive()` en `Product` filtra `existencia='S'` AND (`AGOTADO != 'S'` OR `AGOTADO IS NULL`) — el NULL es crítico, todos los productos activos tienen `AGOTADO = NULL`.

#### Rutas web (SPA catch-all)
```php
// web.php — rutas auth explícitas ANTES del catch-all
Route::get('/login', fn () => view('spa'))->name('login');                          // nombrada 'login' para auth middleware
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/recuperar-password', [ForgotPasswordController::class, 'send'])->name('password.recover');

// SPA catch-all — Vue Router maneja el resto
Route::get('/{any?}', fn () => view('spa'))->where('any', '^(?!api|logout).*$');
```

**Regla crítica:** La ruta `name('login')` DEBE estar en `GET /login` explícito, no en el catch-all. Si el catch-all toma ese nombre, `route('login')` genera `/` y el middleware de auth no redirige correctamente.

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

## Variables globales de configuración

Equivalen a las constantes del `index.php` de la versión CodeIgniter. **Siempre usar `config()` — nunca hardcodear URLs, tokens o correos.**

### APIs externas — `config/services.php`

```php
config('services.berny_api.url')          // URL_API_CUSTOMER — API REST interna
config('services.berny_api.soap')         // URL_APP_SERVICIO — SOAP de pedidos
config('services.sepomex.token')          // TOKEN_SEPOMEX — códigos postales MX
config('services.google_maps.key')        // MAP_API_KEY
config('services.google_maps.key_distributed') // MAP_API_KEY_DISTRIBUTED
```

### Configuración de la app — `config/berny.php`

```php
// Videos YouTube
config('berny.videos.distribuidor')  // URL_VIDEO_DISTRIBUIDOR
config('berny.videos.costo_envio')   // URL_VIDEO_COSTO_ENVIO
config('berny.videos.mejor_precio')  // URL_VIDEO_MEJOR_PRECIO

// Catálogo y promociones
config('berny.catalogo_url')   // URL_APP_CATALOGO — FlipHTML5
config('berny.promo_image')    // IMAGE_PROMOTIONS — 'promomensual.webp'

// Correos de envío (remitentes)
config('berny.emails.ppal')            // web@berny.mx      ← EMAIL_PPAL
config('berny.emails.notificaciones')  // notificaciones@berny.mx
config('berny.emails.aclaraciones')    // aclaraciones@berny.mx
config('berny.emails.activaciones')    // activaciones@berny.mx
config('berny.emails.ferretero')       // servicioalcliente@berny.mx

// Listas de destinatarios de notificaciones internas (arrays)
config('berny.emails.distribuidor_staff')      // ← DISTRIBUTOR_NOTIFICATION_EMAIL
config('berny.emails.pago_cliente_general')    // ← NOTIFICATION_EMAIL_PAYMENT_CUSTOMER_GENERAL
config('berny.emails.pago_pedido')             // ← NOTIFICATION_EMAIL_SEND_PAYMENT_ORDER
config('berny.emails.cuenta_activada')         // ← NOTIFICATION_EMAIL_ACTIVATED_ACCOUNT
config('berny.emails.aclaraciones_staff')      // ← NOTIFICATION_EMAIL_CLARIFICATIONS
config('berny.emails.google_ads')              // ← NOTIFICATION_GOOGLEADS
config('berny.emails.validacion_notificacion') // ← VALIDATE_NOTIFICATION_EMAIL_ADDRESS
```

### Variables de entorno necesarias (`.env`)

```
# API Berny
BERNY_API_URL=
BERNY_SOAP_URL=

# Tokens externos
SEPOMEX_TOKEN=
GOOGLE_MAPS_KEY=
GOOGLE_MAPS_KEY_DISTRIBUTED=

# Mail (contraseñas SMTP van aquí — nunca en código)
MAIL_FROM_ADDRESS="web@berny.mx"
MAIL_FROM_NAME="Berny Distribuidora"

# Correos de envío adicionales
BERNY_EMAIL_NOTIFICACIONES=
BERNY_EMAIL_ACLARACIONES=
BERNY_EMAIL_ACTIVACIONES=
BERNY_EMAIL_FERRETERO=
```

### `window.appData` (Blade → Vue)

Campos expuestos al frontend via `resources/views/layouts/app.blade.php`:

```js
window.appData = {
  user: {                   // null si no autenticado — leído de session('user')
    id, cliente_id, nombre, clave,
    tipo_cliente_id,        // '88721'=WD, '187185'=CR, etc.
    asesor_login_id,        // null para clientes normales; numérico → mostrar "Divisiones"
    solo_vta_contado,       // 'S' → mostrar ChartPieIcon con price_level_reached
    client_type,            // 'WG' | 'WD' | 'CR'
    price_level_reached,    // número actual de nivel (1 por defecto; SP en Fase 3)
    distrib_label,          // 'Quiero Ser Distribuidor' | 'Soy Distribuidor'
    distrib_url,            // '/pages/distributed' | '/spages/dealer_location'
  },
  csrfToken,
  baseUrl,
  catalogo_url,             // config('berny.catalogo_url') — FlipHTML5
  notifications: {          // pedidos_web2 WHERE ESTATUS IN (0,4,5) — query en Blade
    total,                  // suma total
    pedidos,                // estatus 0 — pendientes de confirmar
    pagos,                  // estatus 4 — pagos en línea pendientes
    proceso,                // estatus 5 — en proceso
  },
  errors: {},               // Laravel $errors->toArray() — keyed by field, arrays de strings
  old: {},                  // session()->getOldInput() — repoblar inputs tras fallo de validación
  flash: {
    success,                // session('success')
    error,                  // session('error')
  },
  videos: {
    distribuidor,           // config('berny.videos.distribuidor') — YouTube URL
  },
}
```

**Patrón en páginas Vue con form POST nativo:**
```js
const serverErrors = window.appData?.errors ?? {};
const oldInputs    = window.appData?.old ?? {};
const flash        = window.appData?.flash ?? {};
// Los errores persisten solo en el primer render tras el redirect — se leen una vez.
```

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
| 2b | Auth UX: login, recuperar contraseña, header scroll/responsivo, sesiones (UserSessionService + EnsureUserSession), header con campanita/pastel/catálogo | ✅ Completa |
| 2c | Catálogo: grid categorías, productos, búsqueda relevancia, detalle producto | ✅ Completa |
| 2d | Catálogo: búsqueda devuelve categorías (igual que legacy tienda), mejoras UX | 🔄 Siguiente |
| 3 | Carrito + Precios (via SPs) | ⬜ Pendiente |
| 4 | Checkout + Pagos (Stripe + Conekta) | ⬜ Pendiente |
| 5 | Panel del cliente (port de spages.php) + reset contraseña real | ⬜ Pendiente |
| 6 | API móvil vendedores (/api/v1/vendors/) | ⬜ Pendiente |
| 7 | Cutover a producción (berny.mx) | ⬜ Pendiente |

### Imágenes en `public/images/`
- `logo-black.png` / `logo-black.webp` — logo principal, usado en header y footer (copiado de `bernydist/assets/img/`)
- `Logo-negro.webp` — logo para fondos blancos (usado en LoginPage y ForgotPasswordPage)
- `banner-10.webp` … `banner-50.webp` — banners del carrusel en `/mayoreo`
- `logo_berny.webp`, `logo_silverline.webp`, `logo_tecnolite.webp`, `logo_weston.webp` — logos de marcas

---

## Convenciones

- Controladores de API bajo `app/Http/Controllers/Api/V1/`
- Rutas: `web.php` (público + cliente), `admin.php` (panel interno), `api.php` (móvil)
- Modelos legacy: `$timestamps = false`, `$incrementing = false`, `$guarded = ['PK']`
- Sin comentarios obvios — solo cuando el WHY no es evidente
- No modificar tablas legacy ni crear migraciones que las alteren
