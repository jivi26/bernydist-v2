# MIGRATION.md — Inventario de Estado Actual

**Proyecto:** BernyDist / GomáFácil  
**Fecha de análisis:** 2026-06-10 · Actualizado con schema real: 2026-06-22  
**Autor del análisis:** Claude Code (solo lectura, sin modificaciones)  
**Origen:** PHP 7.4 + CodeIgniter 2 + **MariaDB 11.8.6** (Hostinger Cloud Professional)  
**Destino:** Laravel + Vue.js 3 + Vite  
**BD de trabajo:** Copia en Hostinger, credenciales vía `.env`  

---

## TABLA DE CONTENIDOS

1. [Esquema de Base de Datos](#1-esquema-de-base-de-datos)
2. [Inventario de Código](#2-inventario-de-código)
3. [Riesgos y Dependencias Externas](#3-riesgos-y-dependencias-externas)
4. [Preguntas Abiertas](#4-preguntas-abiertas)

---

## 1. ESQUEMA DE BASE DE DATOS

> **Schema obtenido del dump `schema_bernydist.sql` generado el 2026-06-10.**  
> Motor real: **MariaDB 11.8.6** (no MySQL) — relevante para compatibilidad de drivers en Laravel.

### 1.0 Hallazgo crítico: ya existe un esquema Laravel parcial en la BD

La base de datos contiene **dos mundos coexistiendo**:

| Prefijo | Paradigma | Convención | Estado |
|---|---|---|---|
| Sin prefijo / `_` / ALLCAPS | Legacy CodeIgniter | `latin1_swedish_ci`, ALLCAPS, sin FKs declaradas | En producción activa |
| `bi_` | Laravel (migración parcial ya iniciada) | `utf8mb4_unicode_ci`, snake_case, FKs con `CONSTRAINT` | Parcialmente implementado |

Esto confirma que la migración ya comenzó. Las tablas `bi_*` deben auditarse antes de reutilizarlas.

---

### 1.1 Tablas legacy — Clientes / Geografía

| Tabla | PK | Tipo PK | Collation | Notas críticas |
|---|---|---|---|---|
| `_CLIENTES` | `CLIENTE_ID` | `int(11)` **NO** auto-increment | `latin1_swedish_ci` | 38 columnas. `PASS varchar(150)` (contraseñas en texto plano o hash débil). `ESTATUS char(1)`. `LIMITE_CREDITO decimal(15,2)`. `COMISION_PAGO_WEB double`. `FECHA_ALTA datetime DEFAULT current_timestamp()`. |
| `_DIRS_CLIENTES` | `DIR_CLI_ID` | `int(11)` **NO** auto-increment | `latin1_swedish_ci` | 33 columnas. `UBICACION varchar(50) DEFAULT '0,0'` (coordenadas GPS como string). `VIGENCIA_PRECIOS varchar(10)` (debería ser `date`). `MEMBRESIA_ID`. Contiene RFC, colonia, cruzamientos. |
| `_ESTADOS` | `ESTADO_ID` | `int unsigned` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=104116075` — IDs muy altos, no secuenciales (sync desde ERP). |
| `_CIUDADES` | `CIUDAD_ID` | `int(11)` **NO** auto-increment | `latin1_swedish_ci` | `FECHA_HORA_CREACION datetime` (no timestamp). |
| `_LOCALIDADES` | `LOCALIDAD_ID` | `bigint(20)` **NO** auto-increment | `latin1_swedish_ci` | `TIPO_LOCALIDAD varchar(10)` — clave para cálculo de fletes. `TIPO_ZONA_FLETERA_ID`. |
| `_PAISES` | `PAIS_ID` | `int unsigned` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=167`. |
| `_BR_EMAILS_CLIENTES` | `EMAIL_ID` | `int(11)` DEFAULT 0 | `latin1_swedish_ci` | PK no auto-increment. Flags `ENVIO_DOCTO_FISCAL`, `ENVIO_ORIGINAL`, `ENVIO_COPIA char(1)`. |
| `_ZONAS_CLIENTES` | — | Sin PK declarada | `latin1_swedish_ci` | Solo KEY en `ZONA_CLIENTE_ID`. Sin PK formal. |
| `_OPERADORES_ZONAS` | `(ZONA_CLIENTE_ID, OPERADOR_ID)` | PK compuesta | `latin1_swedish_ci` | Relación M:M operadores-zonas. |
| `DIRECCIONES_GENERALES` | `DIR_ID` | `int(11)` DEFAULT 0 | `latin1_swedish_ci` | `FECHA_CREACION timestamp ON UPDATE current_timestamp()` — ⚠️ se actualiza en cada UPDATE. |

**Tablas `BC` (duplicados para sucursal Baja California):**  
`_CLIENTESBC`, `_DIRS_CLIENTESBC`, `_ESTADOSBC`, `_LOCALIDADESBC`, `_PAISESBC`, `_ZONAS_CLIENTESBC` — misma estructura que sus originales. Confirmarlo: ¿se sincronizan desde el ERP o son réplicas independientes?

---

### 1.2 Tablas legacy — Catálogo de Productos

| Tabla | PK | Tipo PK | Collation | Notas críticas |
|---|---|---|---|---|
| `products` | `id` | `bigint(11)` AUTO_INCREMENT | `utf8mb3_unicode_ci` | `AUTO_INCREMENT=105156654`. `price float` ⚠️ (precisión monetaria). `iva float`. `existencia varchar(1) DEFAULT 'S'`. `ofb varchar(150)` (aliases de búsqueda). `tipo_articulo_flete varchar(10)`. `AGOTADO char(1)`. `TIPO_ARTICULO_VTA varchar(2) DEFAULT 'BR'`. |
| `products_eqm` | `id` | `bigint(11)` AUTO_INCREMENT | `utf8mb3_unicode_ci` | Tabla espejo de `products` para catálogo Equimaq. `TIPO_ARTICULO_VTA DEFAULT 'EQ'`. `ver_en_tienda_web_eqm char(1)`. |
| `brands` | `id` | `int(11)` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=105067086`. `clave char(3)`, `on_top tinyint(1)`. |
| `categories` | `id` | `int(11)` AUTO_INCREMENT | `utf8mb3_unicode_ci` | `AUTO_INCREMENT=105173112`. `is_promo tinyint(1) DEFAULT 0`. `opm varchar(30)`. `orden_catalogo`, `orden_relevancia`. |
| `divisions` | `id` | `int(11)` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=105083284`. `clave varchar(5)`. |
| `groups` | `id` | `int unsigned` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=105150434`. `cmyk_color varchar(50)`. `nombre_etiqueta varchar(50)`. `url_image varchar(50)`. |
| `groups_divisions` | `id` | `int(11)` AUTO_INCREMENT | `latin1_swedish_ci` | `AUTO_INCREMENT=105173115`. Incluye `category_id` además de `group_id`+`division_id`. |
| `EQUIVALENTES` | — | Sin PK, solo índices | `utf8mb4_unicode_ci` | `EQ_ID bigint unsigned`, `ARTICULO_EQ_ID bigint unsigned`, `ARTICULO_ID bigint unsigned`. Sin PK declarada. |
| `IMAGENES_ARTICULOS` | `IMAGEN_ARTICULO_ID` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | FK a `ROL_IMAGEN_ART_ID`. |
| `ROLES_IMAGENES_ARTICULOS` | `ROL_IMAGEN_ART_ID` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | `ES_PPAL varchar(5)` (debería ser `tinyint` o `char(1)`). |
| `INFORMACION_ARTS` | `INFO_ARTICULO_ID` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | `INFORMACION varchar(500)`. Tiene índice en texto. |
| `CARACTERISTICAS_ARTS` | `CARACTERISTICA_ID` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | Dos columnas `timestamp` con `DEFAULT '0000-00-00 00:00:00'` ⚠️. |
| `CLAVES_ARTICULOS` | `CLAVE_ARTICULO_ID` | `int(11)` NO auto-inc | `latin1_swedish_ci` | `ROW_FORMAT=DYNAMIC`. Claves alternativas por rol (`ROL_CLAVE_ART_ID`). |
| `ARTICULOS_GIROS` | `ARTICULO_GIRO_ID` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | |
| `BR_GIROS` | `giro_id` | `int(11)` NO auto-inc | `utf8mb4_unicode_ci` | `tipo char(1)`. |
| `G_Analytics` | `id` | `bigint unsigned` AUTO_INCREMENT | `utf8mb4_unicode_ci` | `AUTO_INCREMENT=103960283`. `views`, `adds`, `purchases int(11) DEFAULT 0`. |

---

### 1.3 Tablas legacy — Sistema de precios multi-lista ⭐ CRÍTICO

> Este subsistema es el más complejo del schema. Los productos tienen **múltiples listas de precio** (`_PRECIOS_EMPRESA`), y cada cliente accede a una lista diferente. Las vistas `products_gmazul`, `products_gmverde`, etc. filtran por `PRECIO_EMPRESA_ID`.

| Tabla | PK | Notas |
|---|---|---|
| `_PRECIOS_ARTICULOS` | `PRECIO_ARTICULO_ID bigint unsigned` AUTO_INCREMENT `=105174816` | `ARTICULO_ID bigint`, `PRECIO_EMPRESA_ID bigint`, `PRECIO double`. Una fila por artículo×lista. |
| `_PRECIOS_EMPRESA` | `Precio_Empresa_id bigint unsigned` AUTO_INCREMENT `=103582442` | `Nombre varchar(150)`, `acepta_envio char(1)`, `importe_min_envio double`. |

**Listas de precio conocidas** (identificadas en vistas):

| `PRECIO_EMPRESA_ID` | Vista asociada | Descripción |
|---|---|---|
| `42` | `products_preciolista` / `lowest_prices` | Precio de lista base |
| `43` | `products_gmazul` | Lista GomáFácil Azul (usa `CLAVES_ARTICULOS` con roles 17/22) |
| `47` | `products_gcontadosc` | Lista GomáFácil Contado |
| `58937` | `products_gmpublic` | Lista GomáFácil Pública |
| `102889139` | `products_gmverde` | Lista GomáFácil Verde |
| `103582441` | `products_gmvip` | Lista GomáFácil VIP |

---

### 1.4 Tablas legacy — Descuentos / Políticas de precio

| Tabla | PK | Collation | Notas |
|---|---|---|---|
| `_POLITICAS_PRECIOS_ART_CLI` | — (solo KEY) | `latin1_swedish_ci` | `APLICA_VIGENCIA char(1)`. `FECHA_INI_VIGENCIA date`, `FECHA_FIN_VIGENCIA date`. |
| `_POLITICA_PRECLI_CLI` | — (solo KEY) | `latin1_swedish_ci` | Asigna política a cliente. |
| `_POLITICA_PRECLI_ZONA` | — (solo KEY) | `latin1_swedish_ci` | Asigna política a zona. |
| `_POLITICA_PRECLI_ART` | — (solo KEY) | `latin1_swedish_ci` | Descuento por artículo+política. `DESCUENTO decimal(15,2)`. |
| `_POLITICA_PRECLI_MARCA` | — (solo KEY) | `latin1_swedish_ci` | Descuento por marca+política. `DESCUENTO double`. |
| `_POLITICA_PRECLI_LINEA` | — | `latin1_swedish_ci` | Descuento por línea+política. |
| `_POLITICA_PRECLI_GRUPO` | — (solo KEY) | `latin1_swedish_ci` | Descuento por grupo línea+política. |
| `_POLITICA_PRECLI_CLI_CONT` | — (solo KEY) | `latin1_swedish_ci` | Política por dirección de cliente (`DIR_CLI_ID`). |
| `_POLITICAS_DSCTOS_ARTCLIVOL` | — (solo KEY) | `latin1_swedish_ci` | `ES_DSCTO_EXCLUSIVO char(1)`. `ACUM_O_SUST char(1)` (acumula o sustituye). `ES_PERMANENTE char(1)`. |
| `_POL_DES_ARTICULO` | — (solo KEY) | `latin1_swedish_ci` | Descuento vol. por artículo. `PORCENTAJE double`, `VOLUMEN int`. |
| `_POL_DES_LINEA` | — (solo KEY) | `latin1_swedish_ci` | Descuento vol. por línea. |
| `_POL_DES_GRUPOLINEA` | — | `latin1_swedish_ci` | Descuento vol. por grupo línea. |
| `_POL_DES_CLIE` | — (solo KEY) | `latin1_swedish_ci` | Asigna política vol. a cliente. |
| `_POL_DES_ZONA` | — (solo KEY) | `latin1_swedish_ci` | Asigna política vol. a zona. |
| `_POL_DES_PREARTCLI` | — | `latin1_swedish_ci` | Relación política precio↔política vol. |
| `_LINEAS_ARTICULOS` | — (sin PK) | `latin1_swedish_ci` | `GRUPO_LINEA_ID`. Dos columnas `timestamp DEFAULT '0000-00-00'` ⚠️. |
| `_GRUPOS_LINEAS` | — (sin PK) | `latin1_swedish_ci` | `CLAVE_GL varchar(30)`. |
| `discount_range` | `id` AUTO_INCREMENT | `latin1_swedish_ci` | `type ENUM('DISTRIBUIDOR','GENERAL','DISTRIBUIDOR_LOC','GENERAL_LOC')`. `initial_amount`, `final_amount`, `initial_percentage`, `final_percentage decimal(18,2)`. Motor del descuento por volumen acumulado. |
| `BR_PROMOCIONES_WEB` | `PROMOCION_WEB_ID` NO auto-inc | `latin1_swedish_ci` | `FECHA_INI date`, `FECHA_VIGENCIA date`, `PCTJ_PROMOCION double`, `CANTIDAD int`. `FECHA_CREACION timestamp ON UPDATE current_timestamp()` ⚠️. |

---

### 1.5 Tablas legacy — Pedidos / Pagos

| Tabla | PK | Collation | Notas críticas |
|---|---|---|---|
| `pedidos_web2` | `pedidos_web_id int unsigned` AUTO_INCREMENT `=61327` | `latin1_swedish_ci` | `ESTATUS varchar(30)` ⚠️ (no ENUM ni int). `TOTAL double` ⚠️. `conekta_id text`, `oxxo_refer text`. `metodo_pago varchar(5)`. `en_crm int(1) DEFAULT 0`. `TIPO_PEDIDO_VTA varchar(2) DEFAULT 'BR'`. |
| `pedidos_web_detalle` | `pedidos_web_detalle_id int unsigned` AUTO_INCREMENT `=1888656` | `latin1_swedish_ci` | `UNIDADES decimal(18,5)`. `PRECIO_UNITARIO decimal(18,6)`. `PCTJE_DSCTO decimal(9,6)`. `PCTJE_DSCTO_CLI/VOL/PROMO decimal(9,6)`. `PRECIO_LISTA decimal(18,6)`. `PCTJ_FLETE decimal(18,2)`. |
| `pedidos_web2_act` | `id bigint` AUTO_INCREMENT `=166555` | `utf8mb4_unicode_ci` | Auditoría de ediciones de pedidos. |
| `conekta_trans` | `id` AUTO_INCREMENT `=25347` | `latin1_swedish_ci` | `client_type enum('credit','count')`. `sync char(1) DEFAULT 'N'`. `comision float`. |
| `conekta_keys` | `id` AUTO_INCREMENT | `latin1_swedish_ci` | Llaves Conekta en BD (¡no en .env!). `type enum('pruebas','produccion')`. `proveedor char(1) DEFAULT 'C'`. |
| `prepago` | `id` AUTO_INCREMENT `=14007` | `latin1_swedish_ci` | `estatus enum('A','C','L')`. `sync char(1) DEFAULT 'N'`. `pedido_ligado int DEFAULT 0`. |
| `folios` | `id` AUTO_INCREMENT | `latin1_swedish_ci` | `serie varchar(15)`, `tabla varchar(100)`, `value int DEFAULT 0`. `ROW_FORMAT=DYNAMIC`. |
| `web_review` | `id bigint unsigned` AUTO_INCREMENT `=19212` | `utf8mb4_unicode_ci` | FK a `pedidos_web2`. `upload char(1) DEFAULT 'N'`. |
| `order_tracking` | `id` AUTO_INCREMENT `=45182` | `utf8mb4_general_ci` | `status int`, `data text`. |
| `_BR_CAB_EDO_CTA_CTE` | — (sin PK) | `latin1_swedish_ci` | Estado de cuenta de clientes. Snapshot sincronizado desde ERP. `FECHA_HORA_ACTUALIZACION timestamp ON UPDATE`. |
| `_BR_DET_EDO_CTA_CTE` | — (sin PK) | `latin1_swedish_ci` | Detalle del estado de cuenta. `ATRASO int`, `DIAS int`. |
| `_DOCTOS_VE` | — (sin PK) | `latin1_swedish_ci` | Documentos de venta del ERP. `TIPO_DOCTO char(1)`. `ESTATUS_WEB varchar(30)`. |
| `_DOCTOS_VE_DET` | `DOCTO_VE_DET_ID int unsigned` AUTO_INCREMENT `=1156901` | `latin1_swedish_ci` | Descuentos a 6 decimales. |
| `_BR_DEPOSITOS_VENDEDORES` | — (sin PK) | `latin1_swedish_ci` | Depósitos de vendedores. |
| `_GUIAS` | — (sin PK) | `latin1_swedish_ci` | Guías de paquetería. |

---

### 1.6 Tablas `bi_*` — Esquema Laravel ya existente ⭐

> Estas tablas fueron creadas por una versión anterior del proyecto de migración. **Ya tienen FK con `CONSTRAINT`, snake_case y `utf8mb4`.** Revisar si son compatibles con el diseño destino o si se reescriben.

| Tabla | PK | Descripción | Estado |
|---|---|---|---|
| `bi_users` | `id bigint unsigned` AUTO_INCREMENT `=240` | Usuarios del sistema Laravel. `role_id`, `customer_id`, `provider`, `uuid`. | Activo |
| `bi_customers` | `id bigint unsigned` AUTO_INCREMENT `=93` | Direcciones de envío/facturación. FK a `bi_users`. `dir_cli_id` (link al legacy). `person_type enum('F','M')`. `location_id`. | Activo |
| `bi_orders` | `id bigint unsigned` AUTO_INCREMENT `=50` | Órdenes Laravel. FK a `bi_customers`, `bi_users`, `bi_coupons`, **`pedidos_web2`**. `status enum(...)` ⚠️ tiene `'En RevisiÃ³n'` (encoding roto). `date_expiry date`, `date_aut datetime`. | Activo — tiene FK cruzada al legacy |
| `bi_order_details` | `id bigint unsigned` AUTO_INCREMENT `=104` | Detalle de órdenes. FK a `bi_orders`. `discount_rate decimal(18,2)`. `is_reject tinyint(1)`. | Activo |
| `bi_order_edits` | `id bigint unsigned` AUTO_INCREMENT `=60` | Historial de ediciones de órdenes. | Activo |
| `bi_order_shipping_methods` | `id bigint unsigned` AUTO_INCREMENT `=50` | `method enum('parcel','on_shop','other')`. | Activo |
| `bi_order_notifications` | `id bigint unsigned` AUTO_INCREMENT `=8` | `type enum('order','payment','account','products')`. | Activo |
| `bi_coupons` | `id bigint unsigned` | `type enum('Importe','Porcentaje')`. FK a `bi_users`. | Activo |
| `bi_discountables` | `id bigint unsigned` | Relación polimórfica cupones↔entidades. | Activo |
| `bi_carts` | `id bigint unsigned` AUTO_INCREMENT `=584` | `is_kit tinyint(1)`. FK a `bi_coupons`. | Activo |
| `bi_payments` | `id bigint unsigned` AUTO_INCREMENT `=3` | `request longtext CHECK(json_valid(...))` — usa JSON MariaDB. Unique en `payment_id`. | Activo |
| `bi_pay_orders` | `id bigint unsigned` AUTO_INCREMENT `=43` | Órdenes de pago Conekta. FK a `pedidos_web2`. `expiration timestamp DEFAULT '0000-00-00'` ⚠️. | Activo — FK cruzada al legacy |
| `bi_product_kits` | `id bigint unsigned` | Kits de productos. FK a `bi_users` y `products`. | Activo |
| `bi_product_kit_details` | `id bigint unsigned` | FK a `bi_product_kits` y `products`. | Activo |
| `bi_liked_products` | `id bigint unsigned` AUTO_INCREMENT `=63` | FK a `bi_users`. | Activo |
| `bi_media` | `id bigint unsigned` | `media_type varchar(255)`, `media_id int`. Media polimórfica. | Activo |
| `bi_banners` | `id bigint unsigned` | `date_scheduled datetime`, `expires_at datetime`. | Activo |
| `bi_flash_sales` | `id bigint unsigned` | `price decimal(18,2)`. `start_at/expires_at datetime`. | Activo |
| `bi_settings` | `id int unsigned` AUTO_INCREMENT `=73` | `key varchar(255)`, `value text`. | Activo |
| `bi_password_resets` | — (solo KEY en email) | | Activo |
| `bi_failed_jobs` | `id bigint unsigned` | | Activo |

---

### 1.7 Tablas de aplicación móvil / app vendedores

| Tabla | PK | Notas |
|---|---|---|
| `_vcliente` / `_vclienteg` | `(CLAVE_CLIENTE, IMEI)` | Login de clientes en app. `ESTATUS DEFAULT 'ACTIVO'`. |
| `_vendedor` / `_vendedorgm` | `OPERADOR_ID` / `CORREO_VENDEDOR` | Login de vendedores. `CLAVES_CLIENTES text` (lista serializada). `IMEI_ACCEDIDOS text`. |
| `_vendedor_imei` | `CLIENTE_ID` | Un IMEI por cliente. `FECHA int(11)` ⚠️ (timestamp Unix como int). |
| `_vendedor_pedidos_app` | `ID int unsigned` AUTO_INCREMENT `=19069` | Pedidos capturados en app. `KeyID varchar(254)`. `ESTATUS int DEFAULT 0`. `precio_empresa_id int`. |
| `_vendedor_pedidos_appGM` | `ID int unsigned` AUTO_INCREMENT `=4407` | Variante GomáFácil. |
| `_vendedor_supervisor` | `id` AUTO_INCREMENT `=26` | Relación vendedor-supervisor. |
| `_visita` | `idvisita` AUTO_INCREMENT `=481539` | Visitas de vendedores. `unique_id varchar(20)`. **Tiene 3 triggers** → `history_store`. |
| `_visitatime` | `id` AUTO_INCREMENT `=22668` | Tiempos de visita. `comporta text`. |
| `_vconfig_app` | `empresa` varchar(254) | Config de la app móvil. Links a APK, precios, etc. |
| `_vconfig_alert` / `_vconfig_alert_` | `ID` AUTO_INCREMENT | Alertas de la app. |
| `USER_ACCESO_JSON` | `EMAIL int unsigned` AUTO_INCREMENT ⚠️ | PK es `EMAIL` pero tipo es `int unsigned`. Mal diseño. |

---

### 1.8 Tablas auxiliares / sistema

| Tabla | Notas |
|---|---|
| `_root` | Usuarios admin del sistema (credenciales en BD, no en .env). `is_staff int`, `only_asig_cli int`. |
| `ci_sessions` | Sesiones CI. **Tiene 2 triggers** que setean `created`/`changed`. No migrar datos. |
| `customer_registration` | Log de eventos de registro/activación/migración de clientes. `type enum('REGISTRO','ACTIVACION','PASSWORD','DIRECCION','PEDIDO','MIGRACION')`. |
| `customer_settings` | `setting_type/setting_value varchar(100)`. AUTO_INCREMENT `=270386`. |
| `bitacora` / `bitacoravisita` | Log de acciones de clientes/visitas. ~126K y ~127K registros. |
| `history_store` | Tabla de sincronización ERP→web. PK compuesta `(table_name(100), pk_date_dest(100))`. `record_state int` (1=insert, 2=update, 3=delete). |
| `_PROCESOS_WEB_MYSQL` | Control de procesos de sincronización. `ERROR char(1) DEFAULT 'N'`. |
| `config` | `key/value varchar(50)`. Config global. |
| `local_keys` | Llaves de licencia local. `fecha_vencimiento datetime`. |
| `log_conekta` | Log raw de webhooks Conekta. |
| `ctalogo_descargas` | Log de descargas del catálogo PDF. `AUTO_INCREMENT=2450`. |
| `temporary_users` | Usuarios temporales en migración. `created_at datetime DEFAULT current_timestamp()`. |
| `cat_montos` / `discount_range` | Catálogos de montos y rangos de descuento. |
| `all_categories_table` | Tabla física (no vista) denormalizada de categorías. Collation `latin1`. |
| `_ztest` | Tabla de pruebas (eliminar). |

---

### 1.9 Tablas de tickets / encuestas

| Tabla | Notas |
|---|---|
| `tickets` | `estatus varchar(10)`. `tipo char(1)` (C=cliente, O=operaciones). `notificado int(1) DEFAULT 0`. `AUTO_INCREMENT=13362`. |
| `tickets_det` | FK a `tickets`. `mostrar_a_clte char(1)`. `fecha_visto datetime`. `fecha_enproceso datetime`. `respuesta_fija varchar(100)`. |
| `ticket_cat_motivo` | `motivo_id int` NO auto-inc. `tipo char(1) DEFAULT 'C'`. `tiempo_respuesta int DEFAULT 48`. |
| `ticket_evidencias` | `public_id varchar(100)` (Cloudinary?). `url varchar(250)`. `AUTO_INCREMENT=664`. |
| `wCuestionario` / `wPreguntas` / `wRespuestas_Operador` / `wCliente_operador` | Módulo de encuestas a operadores. `wCliente_operador` tiene FK a `wCuestionario`. Collation mixta. |

---

### 1.10 Stored Procedures y Functions

| Nombre | Tipo | Propósito | Complejidad |
|---|---|---|---|
| `getDesctoArtCli` | PROCEDURE | Descuento de cliente por artículo. Jerarquía: artículo → marca → línea → grupo línea. | Alta |
| `getDesctoArtCliVol` | PROCEDURE | Descuento por volumen de unidades. 3 cursores: artículo, línea, grupo línea. | Media |
| `getDesctoArtCli_WebExclusive` | PROCEDURE | Descuento exclusivo web (`BR_PROMOCIONES_WEB`). | Baja |
| `getDesctoGralArt` | PROCEDURE | **Descuento total + flete por artículo.** Orquesta todos los SPs de descuento. Calcula: precio\_desc, subtotal, IVA, flete con lógica de niveles de zona. ~200 líneas. | **Muy alta** |
| `getDesctoVolumenAlc` | PROCEDURE | Descuento por monto acumulado en el pedido. Usa `discount_range`. Clasifica cliente: DISTRIBUIDOR/GENERAL/CREDITO. | Alta |
| `getPolDesctoArtCli_id` | PROCEDURE | Obtiene `POLITICA_PRECIO_ART_CLI_ID` del cliente o su zona. | Baja |
| `getPolDesctoArtCliVol_id` | PROCEDURE | Obtiene política de descuento por volumen del cliente o zona. | Baja |
| `CreateDetalle` | PROCEDURE | Crea pedido en `pedidos_web2` + detalle desde `_vendedor_pedidos_app`. Usa cursor. | Media |
| `CreateDetalle2` | PROCEDURE | Inserta el detalle del pedido app. Usado por `CreateDetalle`. | Media |
| `CreateDetalleGM` / `CreateDetalle2GM` | PROCEDURE | Variantes para GomáFácil. | Media |
| `Cuadre_AppF` | FUNCTION | Valida que el pedido app cumpla el monto mínimo. Retorna `double`. | Baja |
| `Cuadre_AppFGM` | FUNCTION | Variante GomáFácil de `Cuadre_AppF`. | Baja |
| `preciosGM` | FUNCTION | Obtiene precio de lista para GomáFácil por opción (1-4). | Baja |

---

### 1.11 Triggers

| Tabla | Trigger | Evento | Acción |
|---|---|---|---|
| `_visita` | `a_i__visita` | AFTER INSERT | Escribe `record_state=1` en `history_store` |
| `_visita` | `a_u__visita` | AFTER UPDATE | Actualiza/inserta `record_state=2` en `history_store` |
| `_visita` | `a_d__visita` | AFTER DELETE | Escribe `record_state=3` en `history_store` (si no era INSERT pendiente) |
| `ci_sessions` | `InsertCi_Session` | BEFORE INSERT | Setea `CREATED` y `changed` a `current_timestamp()` |
| `ci_sessions` | `UpdateCi_Session` | BEFORE UPDATE | Actualiza `changed` a `current_timestamp()` |
| `pedidos_web_detalle` | `pwd_preventDeletion` | BEFORE DELETE | **Bloquea** DELETE si `pedidos_web2.ESTATUS` es `5` o `2`. Lanza `SIGNAL SQLSTATE '45000'`. |

---

### 1.12 Vistas (Views)

| Vista | Tablas base | Propósito |
|---|---|---|
| `all_categories` | `products`, `groups_divisions`, `divisions`, `groups`, `categories`, `brands` + subconsulta precio mínimo | Vista principal del catálogo. Group by división+grupo+categoría+marca. |
| `all_categories_gm` | Ídem + `lowest_prices` | Variante GomáFácil (usa lista precio 42). |
| `all_categories_eqm` | `BR_CAT_*`, `products_eqm`, `brands` | Variante catálogo Equimaq. |
| `all_categories_promo` | `all_categories_gm` + `BR_PROMOCIONES_WEB` | Solo categorías con promociones activas (filtra `curdate() between FECHA_INI and FECHA_VIGENCIA`). |
| `all_categories_old` / `all_categories_gm_old_borrar` | Ídem sin precio mínimo | Versiones antiguas (eliminar). |
| `all_products3` | `products`, `categories`, `groups`, `brands`, `groups_divisions`, `divisions` | Productos con jerarquía completa. Filtra `ARTICULO_EMP_ID <> 2`. |
| `all_products3_promo` | `all_products3` + `BR_PROMOCIONES_WEB` | Productos en promoción activa. |
| `products_promo` | `products` + `BR_PROMOCIONES_WEB` | Join simplificado productos+promos activas. |
| `lowest_prices` | `products` + `_PRECIOS_ARTICULOS` | Precio mínimo por categoría con `PRECIO_EMPRESA_ID = 42`. |
| `products_preciolista` | `products` + `_PRECIOS_ARTICULOS` + `_PRECIOS_EMPRESA` | Precio lista 42 con IVA calculado. |
| `products_gmazul` | Ídem pero con `CLAVES_ARTICULOS` (roles 17+22) | Lista azul, usa clave alternativa del producto. |
| `products_gmverde` / `products_gmvip` / `products_gcontadosc` / `products_gmpublic` | `products` + `_PRECIOS_ARTICULOS` filtrado por ID | Una vista por lista de precio GomáFácil. |
| `X_listaclientes` | `_vendedor`, `_OPERADORES_ZONAS`, `_CLIENTES`, `_DIRS_CLIENTES` | Lista de clientes por vendedor+zona. |
| `productsbc` | `products` filtrado por `linea_articulo_id = '102443617'` | Productos Baja California. |
| `all_products2_del` / `all_products_del` / `all_products3_old_borrar` | Variantes antiguas | Candidatas a eliminar. |

---

### 1.13 Tipos de dato problemáticos — Resumen ejecutivo

| Problema | Tablas afectadas | Acción requerida |
|---|---|---|
| `float` en precios | `products.price`, `products.iva`, `conekta_trans.amount`, `_vendedor_pedidos_app.PRECIO` | Migrar a `decimal(18,2)` en Laravel. Nunca usar `float` para dinero. |
| `varchar(30)` para estado de pedido | `pedidos_web2.ESTATUS` | Definir constantes en Laravel. Posibles valores: `'0','1','2','5'` + strings. Auditar todos los valores únicos. |
| `TIMESTAMP DEFAULT '0000-00-00 00:00:00'` | `CARACTERISTICAS_ARTS`, `_LINEAS_ARTICULOS`, `_GRUPOS_LINEAS` | MariaDB lo permite en modo `NO_STRICT`; MySQL 8 lo rechaza. |
| `TIMESTAMP ON UPDATE current_timestamp()` | `BR_PROMOCIONES_WEB.FECHA_CREACION`, `_BR_CAB_EDO_CTA_CTE`, `DIRECCIONES_GENERALES` | Se actualiza al hacer cualquier UPDATE, no solo al crear. |
| Collation `latin1_swedish_ci` | ~60% de las tablas legacy | Migrar a `utf8mb4_unicode_ci`. Riesgo en strings con acentos/ñ. |
| `utf8mb3` vs `utf8mb4` | `products`, `categories` (utf8mb3), `bi_*` (utf8mb4) | Normalizar a `utf8mb4` en todo el schema nuevo. |
| ENUM en `bi_orders.status` con encoding roto | `bi_orders` | `'En RevisiÃ³n'` debe ser `'En Revisión'`. Corregir antes de migrar. |
| Sin PK formal | `_ZONAS_CLIENTES`, `_LINEAS_ARTICULOS`, `_GRUPOS_LINEAS`, `EQUIVALENTES`, `_BR_CAB_EDO_CTA_CTE`, `_BR_DET_EDO_CTA_CTE`, varias `_POL_*` | No se pueden usar como modelos Eloquent sin declarar `public $primaryKey = null`. |
| `double` para totales monetarios | `pedidos_web2.TOTAL`, `_CLIENTES.LIMITE_CREDITO decimal` (ok), `_POLITICA_PRECLI_ART.DESCUENTO decimal` (ok) | Auditar todos los `double` usados en cálculos financieros. |
| **Motor: MariaDB 11.8.6** (no MySQL) | Toda la BD | Laravel con `mysql` driver es compatible. Verificar que `json_valid()` en `bi_payments` funcione con el driver. |

---

## 2. INVENTARIO DE CÓDIGO

### 2.1 Estructura de carpetas y propósito

```
bernydist/
├── index.php                  # Entry point. Detecta entorno por SERVER_NAME.
├── .htaccess                  # Rewrite a index.php, fuerza HTTPS, base /bernydist.
├── ciapp2.1/                  # Aplicación CodeIgniter (equivale a app/ en Laravel)
│   ├── config/
│   │   ├── config.php         # Base URL dinámica, encryptión key, sesión en BD.
│   │   ├── database.php       # Credenciales local (root) y producción.
│   │   ├── autoload.php       # Carga: database, session, user_agent, stripe, mcatalogo.
│   │   ├── routes.php         # ~50 rutas manuales; multi-tenant por URL prefix.
│   │   ├── hooks.php          # 2 hooks para logging de queries en pages/spages.
│   │   └── acl.php            # Menú admin en JSON.
│   ├── controllers/           # 21 controladores (ver tabla 2.2)
│   ├── models/
│   │   ├── mcatalogo.php      # 1,945 líneas. Motor de catálogo, búsqueda, paginación.
│   │   ├── connection.php     # Bridge CI ↔ Eloquent (usa credenciales del CI_DB).
│   │   ├── dal*.php (×6)      # Modelos Eloquent thin (solo table/primaryKey).
│   │   └── customersetting_model.php  # CI_Model clásico para customer_settings.
│   ├── libraries/
│   │   ├── discounts.php      # Motor de descuentos (llama SP getDesctoArtCli).
│   │   ├── supercart2.php     # Carrito activo (47 KB). supercart2ANT.php = deprecated.
│   │   ├── Stripe.php         # Wrapper Stripe: tokens, clientes, charges, PaymentIntents.
│   │   ├── Websco_controller.php  # Base controller (todos los controllers heredan de aquí).
│   │   ├── Websco_secure.php  # Middleware de autenticación.
│   │   ├── myacl.php          # Control de acceso por rol.
│   │   ├── PHPMailer-6.9.3/   # PHPMailer vía SMTP.
│   │   ├── Stripe/            # Stripe PHP SDK completo.
│   │   ├── TCPDF/ + dompdf/ + FPDF/  # Generación de PDFs.
│   │   └── pct/               # Librería Conekta (OXXO / tarjeta).
│   ├── helpers/
│   │   ├── websco_helper.php  # 32 KB. Helpers de formulario, fechas, email (PHPMailer), callApi().
│   │   └── pagination_helper.php
│   └── views/                 # Vistas PHP/HTML (no listadas en detalle; secundarias en migración).
├── cicore2.1/                 # Core de CodeIgniter 2 (no tocar).
├── assets/                    # CSS, JS, fuentes, imágenes del frontend.
│   ├── vue/                   # Componentes Vue.js (versión no confirmada en código).
│   ├── js/catalogo.js         # 34 KB. Lógica catálogo cliente.
│   ├── js/diseno.js           # 53 KB. Lógica de diseño/landing.
│   └── js/header.js           # 21 KB. Lógica de cabecera.
├── uploads/                   # Archivos subidos (banners, catálogos).
├── vendor/                    # Dependencias Composer (solo illuminate v1.x).
├── node_modules/              # Dependencias npm (Gulp + plugins).
├── gulpfile.js                # Pipeline de build: concat, minify CSS/JS, imagemin.
├── industria/, silverline/, weston/, tecnolite/  # Carpetas multi-marca (PENDIENTE CONFIRMAR uso).
├── cuestions/, siad/          # PENDIENTE CONFIRMAR propósito.
├── info/                      # WordPress con plugin LiteSpeed Cache (¿activo en producción?).
├── wp-cron.php                # Cron trigger: solo escribe timestamp en wp-cron_log.txt.
└── build/                     # Output del build Gulp.
```

### 2.2 Controladores — propósito y tamaño

| Archivo | Tamaño | Propósito |
|---|---|---|
| `pages.php` | 96 KB | Landing pages públicas, rutas multi-marca, descarga de catálogos. |
| `spages.php` | 449 KB | **Panel del cliente**: pedidos, facturas, datos personales, cuentas. El controlador más grande. |
| `main.php` | 81 KB | Webhooks de pago (Stripe, Conekta), notificaciones, CRM. |
| `catalogo.php` | 34 KB | Catálogo principal: listado, filtros, carrito, búsqueda. |
| `tienda.php` | 54 KB | Tienda online: equivalentes, detalle producto, navegación por categoría. |
| `tecnolite.php` | 54 KB | Catálogo específico de marca Tecnolite (lógica casi idéntica a tienda.php). |
| `promocion.php` | 59 KB | Variante catálogo para promociones/liquidaciones. |
| `wscustomer.php` | 52 KB | Web service interno de clientes: contactos, datos, cuenta. |
| `sxapp.php` | 33 KB | API para app móvil. |
| `wsmovil.php` | 13 KB | API móvil ligera. |
| `api.php` | 18 KB | API de descuentos y precios por cliente. |
| `api2.php` | 10 KB | Alternativa/variante de api.php. |
| `menuDinamico.php` | 7 KB | Generación dinámica del menú de navegación. |
| `solicitudes.php` | 6 KB | Gestión de solicitudes/cotizaciones. |
| `catalogo2.php` | 7 KB | Variante/refactor de catalogo.php (en progreso). |
| `catalogoAnt.php` | 31 KB | Versión anterior de catálogo (deprecated). |
| `promocion2.php` | 17 KB | Versión alternativa de promocion.php. |
| `cotizador.php` | <1 KB | Cotizaciones (stub mínimo). |
| `data.php` | <1 KB | Operaciones de datos (stub). |
| `login.php` | <1 KB | Autenticación (renderiza vista). |
| `test.php` | <1 KB | Pruebas manuales (eliminar en migración). |

### 2.3 Dependencias y versiones

#### PHP / Composer (`vendor/composer/installed.json`)

| Paquete | Versión | Nota de migración |
|---|---|---|
| `illuminate/database` | v1.1.1 | ⚠️ Versión de 2013 (Laravel 4.0 era). Incompatible con Laravel 10/11. Los modelos DAL deben reescribirse. |
| `illuminate/support` | v1.1.2 | Ídem. |
| `illuminate/container` | v1.1.0 | Ídem. |
| `illuminate/events` | v1.1.0 | Ídem. |
| **Stripe SDK** | (en `/libraries/Stripe/`) | Verificar versión exacta — migrar al SDK oficial de Composer. |
| **PHPMailer** | 6.9.3 | Compatible; migrar a `phpmailer/phpmailer` vía Composer en Laravel. |
| **TCPDF / dompdf / FPDF** | (en `/libraries/`) | Seleccionar uno para Laravel; `barryvdh/laravel-dompdf` es el más común. |
| **Conekta** | (en `/libraries/pct/`) | Verificar si Conekta tiene SDK oficial para Composer. |

> **⚠️ No existe `composer.json` en el repositorio.** Las dependencias fueron instaladas manualmente y el `vendor/` está versionado directamente.

#### Node.js / npm (`package.json`)

| Paquete | Versión | Uso actual | En destino |
|---|---|---|---|
| `gulp` | ^4.0.2 | Pipeline de build | **Reemplazar con Vite** |
| `gulp-concat` | ^2.6.1 | Concatenación JS/CSS | Vite lo maneja |
| `gulp-minify-css` | ^1.2.4 | Minificación CSS | Vite + PostCSS |
| `gulp-uglify` | ^3.0.2 | Minificación JS | Vite |
| `gulp-imagemin` | ^7.1.0 | Optimización de imágenes | Vite plugin o Laravel Mix |
| `@fortawesome/fontawesome` | ^1.1.8 | Iconos | Actualizar a FA 6 |

#### Librerías frontend (identificadas en `assets/`)

| Librería | Versión | Notas |
|---|---|---|
| Bootstrap | Desconocida (archivos en `assets/css/`) | **PENDIENTE CONFIRMAR** versión exacta. |
| jQuery | Desconocida (archivos en `assets/js/`) | Reducir uso al migrar a Vue 3 reactivo. |
| Vue.js | Desconocida (archivos en `assets/vue/`) | **PENDIENTE CONFIRMAR** si es Vue 2 o Vue 3. |
| Bootstrap-Vue | Referenciado en descripción del proyecto | ⚠️ Bootstrap-Vue es para Vue 2. En Vue 3 se usa BootstrapVue3 o alternativas. |

### 2.4 Capa de acceso a datos

El proyecto usa **dos mecanismos simultáneos**:

#### A) Query Builder nativo de CodeIgniter 2 (`$this->db->...`)
- Usado extensivamente en `mcatalogo.php` (1,945 líneas), `discounts.php`, `spages.php`, `main.php`.
- También usa SQL crudo vía `$this->db->query()` con bindings.
- **Archivos que hablan con la BD directamente:**

| Archivo | Tipo acceso |
|---|---|
| `ciapp2.1/models/mcatalogo.php` | CI Query Builder + SQL crudo |
| `ciapp2.1/libraries/discounts.php` | CI Query Builder + `CALL` SP |
| `ciapp2.1/libraries/supercart2.php` | CI Query Builder |
| `ciapp2.1/controllers/spages.php` | CI Query Builder |
| `ciapp2.1/controllers/main.php` | CI Query Builder |
| `ciapp2.1/controllers/pages.php` | CI Query Builder |
| `ciapp2.1/controllers/catalogo.php` | Delega a mcatalogo.php |
| `ciapp2.1/controllers/tienda.php` | Delega a mcatalogo.php |
| `ciapp2.1/controllers/api.php` | CI Query Builder |
| `ciapp2.1/controllers/wscustomer.php` | CI Query Builder |
| `ciapp2.1/models/customersetting_model.php` | CI_Model nativo |

#### B) Illuminate/Eloquent v1.1.x (vía `connection.php`)
- Bridge manual entre CI y Eloquent antiguo.
- Modelos thin (`dal*.php`) solo definen `$table` y `$primaryKey`.
- Usado para operaciones CRUD simples sobre las tablas `_CLIENTES`, `_DIRS_CLIENTES`, etc.

> **Implicación para migración:** No hay ORM moderno unificado. Todo debe reescribirse con Eloquent de Laravel 10/11.

### 2.5 Puntos de acoplamiento fuerte

#### Con MySQL
- **Stored Procedure `getDesctoArtCli`**: llamado directamente con `CALL`. Si se migra a otro motor (PostgreSQL), debe reescribirse como query Laravel o lógica PHP.
- **Convención de nombres**: tablas con prefijo `_` y nombres en ALLCAPS (ej. `_CLIENTES`, `CLIENTE_ID`) rompen las convenciones de Eloquent. Se necesita mapeo explícito o renombrado en migración.
- **Collation `utf8_general_ci`** (utf8 de 3 bytes): debe actualizarse a `utf8mb4` para emojis y caracteres fuera del BMP.
- **PKs no auto-increment** en tablas de clientes: la lógica de generación de IDs vive fuera del ORM (posiblemente en el ERP). Confirmar antes de migrar.

#### Con Hostinger / Servidor
- **`.htaccess`**: `RewriteBase /bernydist` — hardcodeado para subdirectorio. En producción Laravel se instala en raíz (`/`). Debe eliminarse.
- **Rutas absolutas en TCPDF**: las librerías en `libraries/` usan `__DIR__` y `APPPATH`; no son rutas absolutas del hosting pero sí rutas relativas a la estructura CI.
- **`UPLOAD_PATH = 'uploads/'`**: ruta relativa desde el webroot. Laravel usa `storage/app/public` con symlink. Las rutas deben actualizarse en toda la lógica.
- **Credenciales hardcodeadas**: `ciapp2.1/config/database.php` contiene usuario y contraseña de producción en texto plano. **No usar `.env`** es un riesgo en el repositorio actual.
- **`MISITIO = $_SERVER['SERVER_NAME']`**: detección de entorno por nombre de servidor en lugar de variable de entorno. Laravel usa `APP_ENV` en `.env`.

#### Otros acoplamientos
- **`callApi()`** en `websco_helper.php` llama a rutas internas como `wscustomer/getContactosCliente` usando `file_get_contents` hacia la propia app. Esto asume que el servidor puede hacer peticiones HTTP a sí mismo.
- **WordPress** (`info/` + `wp-cron.php`): coexiste en el mismo webroot. En la migración a Laravel, confirmar si WordPress sigue activo o se elimina.
- **`ciapp2.1/config/database.php`** carga la conexión `intranet` en `cart.php`, pero solo `local` y `production` están definidas. ⚠️ Esto causa un error en runtime — **conexión `intranet` no definida**.

---

## 3. RIESGOS Y DEPENDENCIAS EXTERNAS

### 3.1 Integraciones con APIs / servicios de terceros

| Servicio | Uso | Archivos clave | Riesgo de migración |
|---|---|---|---|
| **Stripe** | Pagos con tarjeta, PaymentIntents, 3D Secure | `libraries/Stripe.php`, `libraries/Stripe/` (SDK), `controllers/main.php::receiveStripe()` | Medio. Migrar SDK a Composer. Verificar versión del SDK vs API version de Stripe. Webhooks deben actualizarse con nueva URL en Dashboard Stripe. |
| **Conekta** | Pagos OXXO y tarjeta (MX) | `libraries/pct/`, `controllers/main.php::receiveConekta()` | Alto. La librería `pct/` es código no estándar. Buscar si Conekta tiene SDK oficial. Webhooks deben actualizarse. |
| **SMTP / Email** | Notificaciones a clientes y admin | `libraries/PHPMailer-6.9.3/`, `helpers/websco_helper.php` | Bajo. PHPMailer migra fácilmente. Usar `laravel/mail` con SMTP en `.env`. |
| **CRM externo** | `getDatosCotizacionAndSaveInCRM()` en `main.php` | `controllers/main.php` | **PENDIENTE CONFIRMAR** qué CRM es y cómo se conecta (¿API REST? ¿DB directa?). |
| **ERP externo** | PKs no auto-increment en `_CLIENTES` sugieren sync con ERP | Modelos DAL | **PENDIENTE CONFIRMAR** si hay sincronización bidireccional con un ERP (¿SAP, CONTPAQi, Aspel?). Crítico para definir la estrategia de PKs. |
| **App móvil** | API endpoints en `sxapp.php`, `wsmovil.php` | Dos controladores | Las rutas cambiarán en Laravel. Si la app está publicada en stores, hay que gestionar versionado de API o backward-compatibility durante transición. |

### 3.2 Funciones específicas de MySQL / Hostinger

| Elemento | Riesgo | Detalle |
|---|---|---|
| SP `getDesctoArtCli` | **Alto** | Si el destino cambia de MySQL, el SP debe portarse. Si se queda MySQL, funciona, pero es mejor reescribir la lógica en PHP/Eloquent para testabilidad. |
| `utf8_general_ci` | Medio | Al migrar a `utf8mb4`, algunos índices de longitud > 191 chars fallan. Revisar índices en el dump. |
| `pconnect = TRUE` | Bajo | Conexiones persistentes MySQL. Hostinger las soporta; verificar límites del nuevo hosting. |
| `mod_rewrite` | Bajo | Estándar Apache; Hostinger lo tiene. Laravel necesita el `.htaccess` propio (generado automáticamente). |
| Cron job externo | Medio | `wp-cron.php` es llamado desde cron del hosting (Hostinger panel). En Laravel, migrarlo a `app/Console/Kernel.php` (scheduler) y configurar un cron real: `* * * * * php artisan schedule:run`. |

### 3.3 Problemas de seguridad que deben resolverse en la migración

| Problema | Severidad | Detalle |
|---|---|---|
| Credenciales de BD en `database.php` en el repo | **Crítica** | Contraseña de producción `H5eAr!Y2Rt|` visible. Mover a `.env` y agregar al `.gitignore`. Rotar la contraseña. |
| Encryption key hardcodeada `'ILoveHer'` | Alta | Mover a `.env` como `APP_KEY`. Regenerar con `php artisan key:generate`. |
| CSRF protection **deshabilitada** | Alta | `$config['csrf_protection'] = FALSE`. Laravel lo activa por defecto; verificar que los formularios y AJAX incluyan el token. |
| XSS global filtering **deshabilitado** | Alta | `$config['global_xss_filtering'] = FALSE`. En Laravel usar `htmlspecialchars` / Blade escaping automático. |
| `ci_sessions` en BD con formato propietario CI | Baja | No migrar sesiones activas; invalidarlas y regenerar con Laravel Session. |

### 3.4 Código muerto / deuda técnica

| Elemento | Situación |
|---|---|
| `catalogoAnt.php` (31 KB) | Deprecated explícito en nombre. Eliminar. |
| `supercart2ANT.php` | Deprecated. Eliminar. |
| `discountsANT.php` | Deprecated. Eliminar. |
| `cart.php` | Referencia conexión `intranet` no definida; clase incompleta (`get_user()` no existe). Eliminar o reimplementar. |
| `api.php` vs `api2.php` | Dos versiones del mismo endpoint. Consolidad en uno. |
| `catalogo.php` vs `catalogo2.php` | Ídem. |
| `promocion.php` vs `promocion2.php` | Ídem. |
| `tienda.php` vs `tecnolite.php` | Lógica casi idéntica (54 KB cada uno). Candidato a clase base + parámetro de marca. |
| `info/` (WordPress) | ¿Sigue activo? Confirmar antes de migrar. |
| `industria/`, `silverline/`, `weston/` | Carpetas multi-marca. Confirmar si están activas en producción. |

---

## 4. PREGUNTAS ABIERTAS

Las siguientes preguntas deben responderse **antes de diseñar el esquema destino en Laravel**.  
Las marcadas con ✅ ya están respondidas.

### Base de datos

✅ **1. Schema exportado** — Resuelto con `schema_bernydist.sql`.  
✅ **2. Motor real** — MariaDB 11.8.6 (no MySQL). Laravel `mysql` driver es compatible.  
✅ **3. `all_categories`** — Es una **vista** (no tabla), definida sobre `products`+`groups_divisions`+`divisions`+`groups`+`categories`+`brands`.  
✅ **4. BD de trabajo** — Copia en Hostinger, credenciales vía `.env`. No se toca la producción.  

**5.** Las tablas `_CLIENTES`, `_DIRS_CLIENTES` y similares tienen PKs **no auto-increment** con IDs de 6-9 dígitos. ¿Los genera el ERP (CONTPAQi/Aspel?) en cada sync, o los asigna la app web en algún flujo? Esto define si en Laravel podemos usar `AUTO_INCREMENT` o debemos respetar los IDs del ERP.

**6.** `pedidos_web2.ESTATUS` es `varchar(30)`. ¿Cuáles son **todos los valores posibles**? Ejecutar: `SELECT DISTINCT ESTATUS FROM pedidos_web2 ORDER BY ESTATUS;` para documentarlos antes de definir los estados en Laravel.

**7.** Las tablas `_BR_CAB_EDO_CTA_CTE`, `_BR_DET_EDO_CTA_CTE`, `_DOCTOS_VE` no tienen PK y parecen snapshots del ERP. ¿Se regeneran completamente en cada sync o se actualizan incrementalmente? ¿Con qué frecuencia corre ese proceso?

**8.** `conekta_keys` guarda las llaves de Conekta **en la base de datos** (no en `.env`). ¿Es intencional para soporte multi-empresa? ¿O se deben mover a `.env` en el nuevo proyecto?

**9.** Las tablas `BC` (ej. `_CLIENTESBC`, `_DIRS_CLIENTESBC`) parecen ser una réplica para la sucursal Baja California. ¿Están activas en producción? ¿La app web las usa directamente o solo el ERP?

**10.** Los SPs `getDesctoGralArt` y `getDesctoArtCli` contienen **toda la lógica de descuentos+flete** en MariaDB. ¿Se planea reescribirlos en PHP/Laravel, o se mantendrán como SPs durante la migración (para no bloquear el go-live)?

### Arquitectura / negocio

✅ **11. BD de trabajo** — Copia en Hostinger, `.env` separado del legacy.  

**12.** Las tablas `bi_*` representan una migración Laravel **ya iniciada**. ¿Están en uso en producción (`bi_orders` tiene 50 registros, `bi_users` tiene 240)? ¿Quién los usa y desde qué app? ¿Hay un frontend Laravel ya en algún servidor?

**13.** `bi_orders.status` ENUM tiene el valor `'En RevisiÃ³n'` con encoding roto. ¿Se puede ejecutar `ALTER TABLE` para corregirlo sin afectar la app actual?

**14.** ¿Las carpetas `industria/`, `silverline/`, `weston/` en el webroot están activas en producción? ¿Son subdominios o subdirectorios?

**15.** ¿Qué CRM usa `getDatosCotizacionAndSaveInCRM()` en `main.php`? ¿Conexión directa a BD o API REST?

### Frontend / app móvil

**16.** ¿Qué versión de Vue.js está en `assets/vue/`? ¿Vue 2 o Vue 3? Bootstrap-Vue solo funciona con Vue 2.

**17.** La app móvil que consume `sxapp.php` / `wsmovil.php` y que escribe en `_vendedor_pedidos_app`: ¿está publicada en stores? ¿Cuántos vendedores la usan activamente? Define si necesitamos versionado de API (`/api/v1/`) durante la migración.

### Hosting / infraestructura

**18.** ¿Hay crons configurados en el panel de Hostinger además de `wp-cron.php`? Ejecutar en el panel: Hosting → Cron Jobs. Los procesos de sync ERP deben identificarse antes de migrar.

**19.** ¿WordPress (`info/`) está activo en producción y comparte esta base de datos, o es un artefacto que se puede eliminar?

---

*Documento actualizado: 2026-06-22 con schema real de `schema_bernydist.sql`.*  
*No se modificó ningún archivo de código del proyecto durante este análisis.*
