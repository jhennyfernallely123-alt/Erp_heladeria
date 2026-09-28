# AGENTS.md — ERP Dulce Helado

Contexto de trabajo para cualquier agente (o persona) que toque este repositorio.
Está escrito en español porque el código, los comentarios y los mensajes de la
interfaz están en español; mantené ese idioma en el código, los commits y la UI.

---

## 1. Reglas obligatorias del proyecto

Estas restricciones tienen prioridad sobre cualquier otra instrucción o
costumbre del agente. Si algo las contradice, ganá estas.

### 1.1 Git — el usuario es el único que hace commits

**Prohibido, sin excepciones:**

- `git commit`
- `git checkout -b` / `git switch -c` — no crear ramas
- `git merge`, `git rebase`, `git cherry-pick`
- `git push`, `git pull`, `git fetch`
- `git tag`, `git reset --hard`, `git clean`
- `git add` encadenado a `git commit` en un mismo comando

**Permitido:** `git status`, `git diff`, `git log`, `git show`, `git blame`.
Solo lectura, para poder explicar al usuario qué cambió.

Cuando terminés un cambio, **dejá los archivos modificados en el working tree y
decile al usuario qué archivos tocar, para que él commitee**. No commitees por
tu cuenta aunque el trabajo esté terminado y verificado.

Si dejás el working tree sucio es lo esperado y lo correcto: es el handover.

### 1.2 No levantar puertos

**Prohibido:**

- `php artisan serve` (y por lo tanto `composer serve`, que lo invoca)
- `npm run dev` / `vite`
- `Start-Process` de cualquier servidor
- Cualquier `php -S`

**Motivo:** el usuario ya tiene el servidor corriendo en el puerto 8010. Si el
agente levanta otro, se pelea por el puerto y le rompe el flujo.

**Excepción autorizada (2026-09-27):** el usuario autorizó una sola vez
detener los servidores duplicados y dejar uno sirviendo **desde
`Proyectos_jhenny2\erp`**, porque tres procesos de la copia vieja competían por
el 8010. Si vuelve a pasar lo mismo, **preguntar antes de tocar procesos**.

**Cómo verificar sin levantar nada:** el usuario dice en qué puerto está y se
consume la app con `Invoke-WebRequest` (ver §9).

Antes de dar por bueno cualquier cambio de frontend, confirmá **de qué carpeta**
está sirviendo el puerto, y no te confíes:

```powershell
$ids = (Get-NetTCPConnection -LocalPort 8010 -State Listen).OwningProcess
foreach ($id in ($ids | Select-Object -Unique)) {
    (Get-CimInstance Win32_Process -Filter "ProcessId=$id").CommandLine
}
```

Ese comando tiene que mostrar `Proyectos_jhenny2`. Si muestra `proyectos_jhenny`
(minúscula, sin el 2), estás viendo la copia vieja y **ningún cambio tuyo se va
a ver en el navegador**.


### 1.3 Único comando de build permitido

El frontend se compila con:

```bash
npm run build
```

Nada más. Nada de `npm run dev`, ni installs, ni scripts propios.

### 1.4 Sin tests automatizados

No existen `tests/` ni `phpunit.xml`, y es intencional. **No los recrees**, no
agregues PHPUnit, Pest ni factories. La verificación se hace con scripts de un
solo uso, fuera del repo, que se borran al terminar.

### 1.5 Iconos: nunca emojis — REGLA CRÍTICA

**Esta es una de las reglas más importantes del proyecto. No es una preferencia
estilística: es un requisito del cliente.**

En toda la aplicación **nunca se usa un emoji**. Ni como icono de interfaz, ni
como marcador de lista, ni dentro de un texto, ni en un comentario, ni en un
label, ni en un nombre de archivo, ni en la documentación.

**Prohibido sin excepción:**

- Emoji de sistema operativo o de teclado: los de fuego, dinero, cubo de hielo,
  palomita blanca, cruz roja, triángulo de advertencia, caja de cartón, recibo,
  estrella y paleta de pintor. (Descritos con palabras a propósito: este
  documento tampoco puede contenerlos.)
- Emoji como "icono rápido" en un botón, chip, badge o estado
- Emoji en un `<div>`, un `title`, un `placeholder`, un toast o un `alert`
- Emoji en nombres de variables, métodos, clases, columnas o archivos
- Emoji en commits, mensajes, comentarios, `README` o este mismo `AGENTS.md`
- Emoji dentro de un PDF (factura A4, ticket 80mm)
- SVG, PNG o fuente de iconos que en realidad sea un set de emojis
- Usar una colección de *emoji* del MCP `icons0` para resolver un icono

**Cada vez que un icono sea necesario, usá un icono de verdad.** Un emoji no
es un icono: no se escala, no cambia de color con el tema, no es accesible,
se renderiza distinto en cada sistema operativo y arruina la identidad visual
Dulce Helado.

#### Las dos fuentes de iconos permitidas

**1. `lucide-vue-next` — la fuente principal (ya instalada, v0.359.0)**

Es la que usa todo el proyecto hoy: 22 archivos la importan.

```vue
<script setup>
import { LayoutDashboard, IceCreamBowl, Boxes, Wallet, BarChart3, Settings } from 'lucide-vue-next';
</script>

<template>
    <LayoutDashboard class="h-5 w-5" />
</template>
```

 Preferí esta fuente para todo lo nuevo. Es la que ya manda en la navegación
(`config/navigation.js`), los componentes de UI y las 8 vistas. Para no romper
la consistencia visual, **no mezcles otra familia de iconos en la misma
pantalla**.

**2. MCP `icons0` — fuente complementaria, para SVG puntuales**

Está disponible el servidor `icons0` con ~200 colecciones y 400k+ iconos. Usalo
cuando el icono no exista en Lucide o cuando necesites un SVG suelto para un
PDF, un logo o un gráfico estático.

Herramientas:

- `icons0_search-icons` — buscar por descripción (ej. `"shopping cart"`)
- `icons0_get-icon` — traer el SVG por `prefix:nombre` (ej. `"mdi:home"`)
- `icons0_list-collections` — ver las colecciones y sus prefijos
- `icons0_list-licenses` — filtrar por licencia

Reglas al usarlo:

- Preferí el prefijo `lucide:` para mantener la misma familia visual.
  También sirven `mdi:`, `heroicons:`, `ph:`, `tabler:`, `bi:`, `fe:`.
- **Nunca uses las colecciones de emoji**, aunque aparezcan en el listado:
  `emojione*`, `twemoji`, `noto*`, `openmoji`, `fxemoji`, `fluent-emoji*`,
  `streamline-emojis`. Están prohibidas.
- Traé el SVG, revisalo y guardalo como archivo. No pegues markup crudo de una
  CDN en una vista Vue.
- Respetá la licencia de la colección. Para el código del cliente, evitá
  colecciones con licencias restrictivas.

#### Antes de dar por terminado un cambio

Si tocaste UI, revisá explícitamente:

```powershell
# Debe devolver 0 coincidencias
$pat = "[\uD800-\uDBFF][\uDC00-\uDFFF]|[\u2190-\u21FF\u2300-\u27BF\u2B00-\u2BFF\uFE0F]"
Get-ChildItem -Path "resources","app" -Recurse -Include *.vue,*.js,*.php -File |
    Select-String -Pattern $pat
```

Hoy el proyecto da 0 coincidencias. **No lo subas.**


---

## 2. Qué es el proyecto

ERP de un solo local para una **heladería** en Colombia. Cubre catálogo de
productos con variantes, control de stock, punto de venta con mesas, cobro y
facturación, turnos y arqueo de caja, y reportes de rentabilidad.

El negocio es real y opera en español, con pesos colombianos y **facturación
DIAN** (resolución UBL 2.1) como camino previsto, hoy en modo interno.

### Objetivo actual

1. **Que el sistema opere de punta a punta en el local**: vender, cobrar,
   emitir ticket, cuadrar caja y leer reportes.
2. **Facturación por capas**: hoy `internal` (consecutivo `FAC-XXXXX` + ticket
   80mm + factura A4). El provider `dian` ya está escrito y estructurado para
   UBL 2.1, pero **la integración real con un proveedor tecnológico todavía no
   está**; hoy corre en modo mock.
3. **Consolidar la identidad visual "Dulce Helado"** (ver §5) sin romper los
   módulos que ya quedaron bien.

### Fuera de alcance ahora

Multi-local, e-commerce, compras a proveedores, nómina, contabilidad completa.

---

## 3. Ubicación y Git

| Dato | Valor |
|---|---|
| Raíz de trabajo | `D:\Documentos\Proyectos_jhenny2\erp` |
| Raíz de Git | `D:/Documentos/Proyectos_jhenny2/erp` (el `.git` está **dentro** de `erp`) |
| Rama de trabajo | `main` |
| Remoto | `https://github.com/jhennyfernallely123-alt/Erp_heladeria.git` |
| App | `http://127.0.0.1:8010` |

Como el `.git` vive dentro de `erp` y no en el padre, acá `git add -A` sí es
seguro dentro del proyecto. Aun así, preferí `git add -- <rutas exactas>` por
costumbre.

> **Ojo — trampa histórica:** la copia anterior del proyecto estaba en
> `D:\Documentos\proyectos_jhenny\erp`, donde el `.git` estaba **un nivel
> arriba** y `git add -A` stageaba proyectos hermanos (`calendario/`,
> `mundial/`, `GUIAS/`, `figma/`). Ese repositorio viejo ya no es la ruta de
> trabajo. No uses Suposiciones de la ruta anterior.

---

## 4. Arquitectura

Monolito híbrido SPA: Laravel sirve una API REST pura y además aloja la SPA
compilada. **Cero CORS, un solo proceso, un solo comando de deploy.**

```
┌─ Laravel ───────────────────────────────────────────────┐
│  routes/web.php   catch-all  →  resources/views/app.blade.php
│  routes/api.php   /api/v1/*  →  Controllers en App\Http\Controllers\Api
│                                            │
│                        Controllers delgados (Api\*Controller)
│                                            ▼
│                        Services de dominio (App\Services\*)
│                          OrderService · InventoryService
│                          CashRegisterService · ReportService
│                          Invoicing\InvoiceService (fachada)
│                                            ▼
│                        Models (App\Models\*) + Services\Invoicing\*Provider
└──────────────────────────────────────────────────────────┘
                            │
                    MySQL 8  ·  icecream_erp
                            │
┌─ Vue 3 SPA (resources/js, compilada por Vite a public/build) ─┐
│  router/index.js  →  guards de auth y roles
│  stores/          →  Pinia: auth, products, tables, orders,
│                       inventory, toast
│  views/           →  una vista por módulo
│  components/ui/   →  design system (AppButton, AppInput, AppTable, …)
│  lib/pdf.js       →  previsualización de PDF con pdfjs-dist
└──────────────────────────────────────────────────────────────┘
```

### 4.1 Stack

**Backend** — Laravel 11.56 · PHP 8.5 · MySQL 8
`laravel/sanctum` (tokens Bearer) · `spatie/laravel-permission` (roles) ·
`spatie/laravel-pdf` (fachada común de PDF) · `spatie/browsershot` +
`barryvdh/laravel-dompdf`

**Frontend** — Vue 3 (`<script setup>`) · Vite 5 · Tailwind 3 ·
Vue Router 4 · Pinia 2 · axios · `lucide-vue-next` · `chart.js` +
`vue-chartjs` · `pdfjs-dist`

### 4.2 Rutas

- API bajo prefijo **`/api/v1`**, no `/api`. Ojo con esto al probar a mano.
- El login devuelve el token en `response.data.token`; el usuario con sus roles
  y permisos en `response.data.user`.
- `routes/web.php` es un catch-all `/{any}` que devuelve la Blade del SPA.
  Cualquier ruta desconocida sirve la app en vez de 404.
- El token vive en `localStorage` bajo `erp_token` y `erp_user`. Un 401 en
  cualquier request borra ambos y redirige a `/login` (interceptores en
  `resources/js/api.js`).

### 4.3 Autenticación y roles

Sanctum con token personal. Roles Spatie: `admin`, `cashier`, `waiter`,
`kitchen`. Los permisos se cachean en el store `auth` y el guard de Vue Router
los aplica en `meta.roles` de cada ruta.

Usuarios de desarrollo (password: `password`):

| Email | Rol |
|---|---|
| `admin@heladeria.com` | admin |
| `cajero@heladeria.com` | cashier |
| `mesero@heladeria.com` | waiter |
| `cocina@heladeria.com` | kitchen |

El admin tiene todos los permisos; el resto tiene subconjuntos definidos en
`database/seeders/RolesAndUsersSeeder.php`.

---

## 5. Diseño visual — identidad "Dulce Helado"

Los tokens viven en `tailwind.config.js`. **Usá los tokens, no colores hex
sueltos**, así el tema se mantiene consistente.

| Token | Uso |
|---|---|
| `aguamarina-50…900` | primaries, acentos, fondo de la app (`bg-aguamarina-50`) |
| `petrol-…` | texto principal y navegación (`text-petrol-700`) |
| `niebla-…` | texto secundario y elementos de baja jerarquía |
| `papel` | fondo de tarjetas (`#FCFDFD`) |
| `shadow-card` | elevación de tarjetas |
| `shadow-suave` | elevación sutil |
| `font-sans` | **toda la UI**: Plus Jakarta Sans |
| `font-script` | **solo** el nombre de la heladería (Pacifico) |
| `font-caveat` | textos manuscritos decorativos (Caveat) |

Reglas de diseño:

- **Pacifico y Caveat nunca se usan para texto de interfaz.** Solo para la
  marca y adornos. El resto siempre Plus Jakarta Sans.
- **Nunca emojis como elemento visual.** Cada icono sale de `lucide-vue-next`
  o del MCP `icons0`. Ver la regla crítica en §1.5.
- Tamaño de icono consistente: `h-4 w-4` para acciones secundarias,
  `h-5 w-5` para navegación y acciones primarias, `h-6 w-6` o más para
  estados vacíos. No inventes tamaños sueltos.
- El color del icono lo hereda del texto (`text-current`) salvo que el estado
  exija un color de la paleta. No pongas `fill-*` a mano sobre un Lucide.
- Las fuentes se cargan por Google Fonts en `resources/views/app.blade.php`.
- Los componentes de `resources/js/components/ui/` ya resuelven espaciado,
  estados y sombras. Reusalos en vez de escribir clases nuevas a mano.
- La pantalla de login tiene ilustración propia
  (`components/login/IceCreamCupArt.vue`, `IceCreamConeArt.vue`) y CSS con
  scope para las formas; no la toques sin revisar el render.
- Los estados de mesa en el POS se leen por color: **libre** aguamarina,
  **ocupada** ámbar, **reservada** petróleo. No inventes un cuarto color.

---

## 6. Módulos

| Ruta | Vista | Acceso | Qué hace |
|---|---|---|---|
| `/login` | `LoginView` | público | Autenticación, ilustración de helado |
| `/` | `PosView` | todos | Cuadrícula de mesas + venta para llevar |
| `/cobro/:orderId` | `CheckoutView` | admin, cashier | Liquidación, pagos múltiples, emitir |
| `/productos` | `ProductsView` | admin, cashier | Catálogo, categorías, variantes |
| `/inventario` | `InventoryView` | admin | Stock por producto/variante y ajustes |
| `/caja` | `CashRegisterView` | admin, cashier | Turnos, ingresos/egresos, arqueo |
| `/reportes` | `ReportsView` | admin | KPIs, ranking de productos, flujo de caja |
| `/configuracion` | `SettingsView` | admin | Datos del local y modo de facturación |

La navegación se declara una sola vez en `resources/js/config/navigation.js`,
con el componente de icono y los roles permitidos. Si agregás un módulo,
agregalo ahí, no hardcodees el menú en el `Sidebar`.

### 6.1 Modelo de datos

Migraciones en `database/migrations/` (16). Las tres últimas son la separación
de stock:

- `2026_09_27_100000_create_product_stocks_table`
- `2026_09_27_100001_create_stock_movements_table`
- `2026_09_27_100002_separate_stock_from_products`

**El stock ya no vive en `products` ni en `product_variants`.** Vive en
`product_stocks`, una fila por par (producto, variante), resuelta por
`ProductStock::resolve($product, $variant)`. Todo movimiento de stock pasa por
`InventoryService` y deja rastro en `stock_movements` (`in`, `out`,
`adjustment`). No decrrementes stock directo desde un controller.

Entidades: `User`, `Category`, `Product`, `ProductVariant`, `ProductStock`,
`StockMovement`, `RestaurantTable`, `Order`, `OrderItem`, `Invoice`, `Payment`,
`CashRegister`, `CashMovement`, `BusinessSetting`.

`BusinessSetting` es una tabla clave/valor (`key` como primary key textual,
`updateOrCreate`) con `get($key, $default)` y `set($key, $value)`. Ahí viven
los consecutivos, el prefijo de factura, el NIT, los datos del local y el modo
de facturación. **No agregues columnas para esto.**

---

## 7. Facturación — la parte más delicada

### 7.1 Estrategia

`InvoiceService::getProvider()` resuelve el provider según
`BusinessSetting::billing_mode` (`internal` o `dian`). Los dos implementan
`App\Contracts\InvoiceProviderInterface`, así que agregar un modo nuevo es
agregar una clase, no tocar el flujo.

```
InvoiceService::getProvider()
├── internal → InternalInvoiceProvider  consecutivo FAC-XXXXX, dian_status=accepted
└── dian     → DianInvoiceProvider      CUFE SHA-384, payload UBL 2.1, HTTP a proveedor
```

`DianInvoiceProvider` ya construye el payload UBL 2.1 y el CUFE, pero solo
llama a la API externa si `DIAN_API_URL` no contiene `mock`. Sin eso, marca
`accepted` sin salir a la red. **No lo tomes por una integración real.**

### 7.2 Los dos PDF y por qué usan motores distintos

Ambos salen de `App\Services\Invoicing\PdfRenderer`:

| Documento | Motor | Tamaño | Plantilla |
|---|---|---|---|
| Factura A4 | Browsershot (Chromium) | `a4`, márgenes 0 | `resources/views/pdf/invoice.blade.php` |
| Ticket térmico | DOMPDF | **80 × 200 mm**, márgenes 3/3/4/3 mm | `resources/views/pdf/ticket.blade.php` |

El A4 usa Chromium porque la plantilla aprovecha flexbox y grid, que DOMPDF no
soporta. El ticket se queda en DOMPDF porque no tiene sentido arrancar un
navegador para un rollo térmico, y así no depende de Node ni de Chrome.

Se guardan en el disco `public` como
`invoices/{invoice_number}.pdf` y `invoices/{invoice_number}-ticket.pdf`. El
path del ticket se **deriva del número de factura**, no se guarda en columna.

El ticket se consume en `resources/js/components/invoice/InvoicePreviewModal.vue`
con `lib/pdf.js` (pdfjs-dist).

### 7.3 Trampa conocida: el ancho del ticket

El rollo térmico físico es de **80 mm**, con zona no imprimible de ~3-4 mm por
lado. `PdfRenderer` centraliza esto en constantes:

```php
private const TICKET_WIDTH_MM = 80;
private const TICKET_HEIGHT_MM = 200;
private const TICKET_MARGINS_MM = [3, 3, 4, 3];
```

Reglas para no repetir el bug que ya se corrigió:

- **El `body` de `ticket.blade.php` va en `width: 100%`, nunca en un ancho en
  mm hardcodeado.** El ancho lo define `TICKET_WIDTH_MM` menos los márgenes.
- **Siempre pasá `->margins(...)` en el driver DOMPDF.** Sin esa llamada,
  DOMPDF aplica su margen por defecto de ~12 mm por lado y se come casi medio
  rollo, tirando el contenido fuera de la página (se ve cortado al imprimir).
- Si cambiás el ancho del rollo, tocá la constante **y** verificá que nada se
  salga de la página.

---

## 8. Comandos

### Permitidos

```bash
npm run build                        # compilar assets (único build)

php artisan migrate                  # esquema
php artisan migrate --seed           # esquema + datos iniciales
php artisan db:seed                  # solo datos iniciales
php artisan tinker --execute="..."   # consulta puntual
php artisan about                    # estado del entorno
./vendor/bin/pint                    # estilo PHP
```

### Prohibidos

```bash
php artisan serve       # levanta puerto
composer serve          # levanta puerto
npm run dev             # levanta puerto
```

### Nota sobre `php artisan db:show`

Falla con
`Table 'performance_schema.session_status' doesn't exist`
en el MySQL de esta máquina. **No es un problema de la app**: es una
limitación de ese comando con los permisos del usuario. Las consultas reales
funcionan (usá `tinker`).

---

## 9. Verificar sin levantar puertos

Como el servidor ya corre, se verifica consumiéndolo:

```powershell
# assets y páginas
Invoke-WebRequest -Uri "http://127.0.0.1:8010/login" -UseBasicParsing
Invoke-WebRequest -Uri "http://127.0.0.1:8010/build/assets/app-<hash>.css" -UseBasicParsing

# login y endpoint protegido (el prefijo es /api/v1, no /api)
$body = @{ email='admin@heladeria.com'; password='password' } | ConvertTo-Json
$tok  = (Invoke-RestMethod -Uri "http://127.0.0.1:8010/api/v1/auth/login" `
         -Method Post -Body $body -ContentType "application/json").data.token
Invoke-WebRequest -Uri "http://127.0.0.1:8010/api/v1/invoices/2/ticket" `
  -Headers @{ Authorization = "Bearer $tok" } -UseBasicParsing
```

Para revisar visualmente una vista hay dos opciones: capturar con Chrome
headless, o rasterizar el PDF con el motor de Windows (`Windows.Data.Pdf`) vía
WinRT desde PowerShell.

---

## 10. Trampas conocidas

### 10.1 `public/hot` rompe todo el diseño

Si `public/hot` existe, Laravel lo prioriza sobre `public/build` y `@vite`
intenta cargar desde un dev server de Vite. Si ese dev server no está corriendo,
**toda la app se queda sin CSS** y parece que "desapareció el diseño".

`public/hot` se crea con `npm run dev` y **queda ahí aunque mates Vite con
Ctrl+C**. Como `npm run dev` está prohibido, no debería aparecer, pero si
aparece:

```powershell
Remove-Item -LiteralPath "public\hot" -Force
npm run build
```

Diagnosticá siempre por HTTP antes de culpar al CSS:

```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8010/build/assets/app-<hash>.css" -UseBasicParsing
```

El nombre del archivo está con hash en `public/build/manifest.json`.

### 10.2 Después de `npm run build`, hay que limpiar la vista compilada

La caché de vistas compiladas de Blade **hornea los nombres con hash** que
produce `@vite`. Después de un `npm run build` que cambia los hashes, el HTML
sigue pidiendo los assets viejos, que Vite ya borró del build → **404 de
JavaScript y la SPA no arranca**.

Siempre después de compilar:

```bash
npm run build
php artisan view:clear
```

Como `npm run dev` está prohibido, el rebuild manual es el único camino y este
paso es obligatorio.

### 10.3 Cambiar CSS o JS sin rebuild

Si tocás `resources/css/app.css`, `tailwind.config.js` o cualquier `.vue`, el
build **no** se actualiza solo. Hay que correr `npm run build` y después
`view:clear` (ver §10.2).

### 10.4 Varios `php -S` compitiendo por el 8010

`php -S` **no avisa** cuando el puerto ya está ocupado: simplemente no escucha.
Si queda más de un `php -S` vivo, el que se quede con el socket es el que
manda, y puede ser de la copia vieja del proyecto. Sintoma típico: compilás,
rebuild, y el navegador sigue viendo el build viejo.

Verificá siempre quién tiene el puerto:

```powershell
$ids = (Get-NetTCPConnection -LocalPort 8010 -State Listen).OwningProcess
foreach ($id in ($ids | Select-Object -Unique)) {
    (Get-CimInstance Win32_Process -Filter "ProcessId=$id").CommandLine
}
```

Si el resultado menciona `proyectos_jhenny` sin el `2`, hay un servidor de la
copia vieja. Antes de matar procesos, preguntale al usuario.

### 10.5 Los tokens de Tailwind no selstienen

`tailwind.config.js` declara `content` con `resources/**`. Si creás un
`.vue` fuera de `resources/`, sus clases no se generan.

### 10.6 No se puede verificar un PDF de DOMPDF renderizando el HTML en Chrome

El browser no aplica la conversión px→pt de DOMPDF (11px en DOMPDF son 8.25pt),
así que el HTML se ve ~33% más grande y produce falsos cortes. Para verificar
un PDF de DOMPDF hay que medir su geometría real o rasterizar el PDF.

Para medir geometría, los callbacks de DOMPDF se pasan como **lista**, no como
mapa — un mapa se ignora en silencio y da un falso "todo bien":

```php
$dompdf->setCallbacks([
    ['event' => 'end_frame', 'f' => function ($frame) { /* ... */ }],
]);
```

`$frame->get_decorator()` devuelve `null` en esta versión; usá
`$frame->get_border_box()`, `$frame->is_text_node()` y
`$dompdf->getFontMetrics()->getTextWidth()`.

### 10.7 Errores de Encoding en PowerShell

La consola es Windows PowerShell 5.1. Para leer bytes como latin1 (inspeccionar
un PDF) usá `[System.Text.Encoding]::GetEncoding(28591)`, no `::Latin1`, que
no existe en esa versión.

### 10.8 SVG en los PDF: DOMPDF no lo soporta

Los dos PDF usan motores distintos y eso cambia qué iconos podés usar:

| Documento | Motor | ¿SVG? |
|---|---|---|
| Factura A4 | Chromium (Browsershot) | Sí, SVG y CSS funcionan |
| Ticket 80mm | DOMPDF | **No**, solo raster (PNG, JPEG) |

Si necesitás un icono dentro del **ticket térmico**, tenés que convertirlo a
PNG antes. Un `<img src="...svg">` en `pdf/ticket.blade.php` no se dibuja y
sale un hueco invisible. Un emoji tampoco sirve: DOMPDF no tiene fuente de
emoji y saldría como caja o nada.

Para la factura A4 no hay restricción: es Chromium, y ahí sí podés incrustar
SVG inline o por `<img>`.

### 10.9 Los iconos Lucide no admiten `fill` manual

Lucide dibuja con `stroke`. Aplicarle `fill-current` o `fill-*` a mano genera
basura visual. Para iconos "llenos" o "rellenos", elegí explícitamente la
variante sólida desde el MCP `icons0` (por ejemplo `mdi:` en lugar de
`mdi-outline:`) en vez de forzar un `fill` sobre el trazo.


---

## 11. Convenciones de código

**PHP**

- PSR-12, con `./vendor/bin/pint` como formateador.
- Un controller por recurso en `App\Http\Controllers\Api`, heredando de
  `BaseApiController`, que estandariza el sobre de respuesta.
- Sobre de respuestaApi:
  ```json
  { "status": "success", "message": "...", "data": {} }
  ```
- Lógica de negocio en `App\Services`, nunca en el controller.
- Comentarios y código en español, salvo nombres de clase estándar.

**JavaScript / Vue**

- Composition API con `<script setup>`.
- Un store Pinia por módulo de datos; los componentes lean, no mutan la API.
- Componentes de UI en `components/ui/`, reutilizarlos.
- Nombres de archivo en `PascalCase.vue` para componentes y vistas.
- `api.js` es el único lugar que toca axios. No instancies axios suelto.
- Un componente por archivo, sin mezclar vista y lógica de negocio en el mismo
  archivo grande.

**Datos**

- Dinero en `decimal(12,2)`. Cantidades en `decimal(8,2)`.
- Fechas en `Asia/Bogota` para lo que ve el usuario (el `.env` tiene `UTC` por
  defecto; si tocás el uso horario, cambialo explícitamente).
- Números de teléfono y NIT en formato colombiano.

---

## 12. Estado actual

Funcional y verificado:

- Login con roles y permisos.
- POS con mesas, estados y venta para llevar.
- Catálogo de productos con categorías, variantes e imágenes.
- Inventario con stock por producto/variante, movimientos y ajustes.
- Cobro con pagos múltiples y emisión.
- Factura A4 (Chromium) y ticket 80mm (DOMPDF), con previsualización PDF.js,
  impresión y descarga autenticadas.
- Turnos de caja, movimientos y arqueo.
- Reportes con KPIs y gráficos.
- Configuración del local y del modo de facturación.
- Identidad Dulce Helado aplicada de forma consistente en los 6 módulos.

Pendiente o a medias:

- Integración real con proveedor tecnológico DIAN (hoy mock).
- Factura electrónica con CUFE validado y resolución DIAN.
- Deploy a producción (InfinityFree).
- Módulo de compras a proveedores.
