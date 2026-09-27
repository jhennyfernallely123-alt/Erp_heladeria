# Módulo de Productos independiente del Stock + Rediseño de interfaz

**Fecha:** 2026-09-27
**Alcance:** Shell de la aplicación + sistema de diseño + módulos Productos e Inventario
**Fuera de alcance:** migración de las otras 6 vistas al sistema de diseño (POS, Caja, Cobro, Reportes, Configuración, Login)

---

## 1. Objetivo

Separar conceptualmente el **producto** (su identidad y su valor) del **stock** (cuánto hay disponible), y reconstruir la interfaz siguiendo la referencia visual entregada: sidebar oscuro con iconos, barra superior con chip de usuario, encabezado de página, tabla limpia con badges de estado, iconografía de acciones y paginación en el pie.

Regla rectora: **el módulo Productos no muestra, no pide y no expone información de stock.** Productos = qué es el producto, cómo se llama, a qué categoría pertenece, qué imagen tiene, cuánto cuesta y cuánto vale. Inventario = cuánto hay.

Regla de iconografía: **se usan icons de `lucide-vue-next`, nunca emojis.** Esto aplica a todo el trabajo de este alcance, incluidos los iconos de reemplazo de las imágenes de producto.

---

## 2. Decisiones tomadas

| Tema | Decisión |
|---|---|
| Alcance | Shell + sistema de diseño + Productos + Inventario primero |
| Separación de stock | Real en base de datos: tablas `product_stocks` y `stock_movements` |
| Columnas de Productos | Imagen, Nombre, Categoría, Precio, Estado, Acciones (sin Stock) |
| Imagen de producto | Columna `image` + subida de archivo (2MB máx); fallback a icono lucide de la categoría |
| Iconografía | Solo `lucide-vue-next`, cero emojis |
| Menú lateral | 6 entradas reales: Inicio, Productos, Inventario, Caja, Reportes, Configuración |
| Paleta | Índigo `#6368f1` como acento; la paleta rosa `ice` deja de usarse |
| Búsqueda | TopBar limpia; el buscador vive en la tarjeta del módulo, con contexto |
| Variantes | Se mantienen (están conectadas a facturación DIAN y reportes) |
| Eliminar | Borrado lógico (`deleted_at`) + badge Activo/Inactivo |

---

## 3. Sistema de diseño

### 3.1 Tokens

Tailwind 3.4 con la paleta `ice` existente conservada en `tailwind.config.js` pero sin uso. Acento índigo:

| Uso | Color |
|---|---|
| Fondo de página | `#f1f5f9` (`slate-100`) |
| Card | `#ffffff` |
| Borde de card | `#e2e8f0` (`slate-200`) |
| Acento / primario | `#6366f1` (`indigo-500`) |
| Hover de acento | `#4f46e5` (`indigo-600`) |
| Texto principal | `#1e293b` (`slate-800`) |
| Texto secundario | `#64748b` (`slate-500`) |
| Sidebar | `#1e293b` (`slate-800`) |

Escala de badges de estado:

| Estado | Fondo | Texto |
|---|---|---|
| Activo / Normal | `emerald-50` | `emerald-700` |
| Inactivo / Bajo | `amber-50` | `amber-700` |
| Crítico | `rose-50` | `rose-700` |

### 3.2 Primitivos

Todos en `resources/js/components/ui/`. Ninguno depende de un módulo concreto.

| Componente | Responsabilidad |
|---|---|
| `AppButton.vue` | Variantes `primary`, `secondary`, `ghost`, `danger`. Tamaños `sm`, `md`. Recibe `icon` (componente lucide) y texto opcional. Estados `loading` y `disabled`. |
| `AppIcon.vue` | Envoltura de un icono lucide con `size` y `stroke-width` por defecto. Punto único de acceso a la librería. |
| `AppModal.vue` | Overlay + diálogo. Props `open`, `title`, `size`. Emite `close`. Cierra con `Escape` y con clic en el overlay. Bloquea el scroll del body mientras está abierto. |
| `AppInput.vue` | Input con label, placeholder, hint y mensaje de error. `v-model` con `defineModel`. |
| `AppSelect.vue` | Select con label y error. `v-model` con `defineModel`. |
| `AppTextarea.vue` | Textarea con label y error. |
| `AppTable.vue` | Tabla con slots `thead`, `default`, y estados de carga (skeleton) y vacío. Renderiza `AppEmptyState` cuando no hay filas. |
| `AppBadge.vue` | Badge de tono `neutral`, `success`, `warning`, `danger`, `info`. |
| `AppPagination.vue` | Pie de tabla: "Mostrando X-Y de Z registros" + control de páginas. Props `total`, `page`, `perPage`. Emite `update:page`. |
| `AppEmptyState.vue` | Estado vacío con icono lucide, título y descripción. |
| `ConfirmDialog.vue` | Confirmación de acciones destructivas. Se apoya en `AppModal`. |
| `StatCard.vue` | Tarjeta de métrica: icono, etiqueta, valor, tono de color. |
| `PageHeader.vue` | Título, subtítulo y slot de acciones a la derecha. |
| `AppToast.vue` | Notificaciones de éxito/error. Store `toast.js` con `push(tipo, mensaje)`. |

### 3.3 Shell

`AppShell.vue` parte la pantalla en sidebar fijo de 256px (`w-64 shrink-0`) y una columna principal con scroll propio.

`TopBar.vue` vive dentro de la columna principal, encima del contenido. Contenido: marca a la izquierda (icono `IceCreamBowl` + "Nieve Real" + "Heladería & POS"), y a la derecha el chip de usuario — avatar circular con iniciales, nombre, rol en mayúsculas, chevron — que abre un menú con "Cerrar sesión". Sin buscador: la búsqueda vive en la tarjeta de cada módulo.

`Sidebar.vue` se vuelve data-driven desde `resources/js/config/navigation.js`:

```js
export const navItems = [
  { label: 'Inicio',       route: '/',             name: 'pos',            icon: LayoutDashboard, roles: ['admin', 'cashier'] },
  { label: 'Productos',    route: '/productos',    name: 'products',       icon: IceCreamBowl,     roles: ['admin', 'cashier'] },
  { label: 'Inventario',   route: '/inventario',   name: 'inventory',      icon: Boxes,            roles: ['admin'] },
  { label: 'Caja',         route: '/caja',         name: 'cash-register',  icon: Wallet,           roles: ['admin', 'cashier'] },
  { label: 'Reportes',     route: '/reportes',     name: 'reports',        icon: BarChart3,       roles: ['admin'] },
  { label: 'Configuración',route: '/configuracion',name: 'settings',       icon: Settings,         roles: ['admin'] },
]
```

Un item se muestra si el usuario tiene alguno de sus `roles`. El item activo se marca con fondo índigo y una barra lateral clara de 3px.

`App.vue` pasa a renderizar `<AppShell>` envolviendo el `<router-view>`, en lugar del `<Sidebar>` actual.

---

## 4. Modelo de datos

### 4.1 Cambios en `products`

| Cambio | Detalle |
|---|---|
| Eliminar | `stock_quantity`, `min_stock_alert`, `stock_type` |
| Agregar | `image` string nullable — ruta relativa dentro de `storage/app/public/products` |
| Agregar | `deleted_at` timestamp nullable (SoftDeletes) |

Se conservan `category_id`, `name`, `description`, `cost_price`, `sale_price`, `has_variants`, `is_active`.

### 4.2 Cambios en otras tablas

`order_items.product_id`: `cascadeOnDelete()` → `nullOnDelete()`. Hoy, borrar un producto que ya se vendió destruye el historial de pedidos y, en cascada, facturas y pagos. Este es un bug de pérdida de datos que se corrige en el mismo lote.

### 4.3 Tabla `product_stocks`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | |
| `product_id` | foreignId | FK → `products`, cascadeOnDelete |
| `product_variant_id` | foreignId nullable | FK → `product_variants`, cascadeOnDelete |
| `quantity` | decimal(12,2) | default 0 |
| `min_alert` | decimal(12,2) | default 5 |
| `stock_type` | enum(`unit`,`bulk_grams`) | default `unit` |
| timestamps | | |

Unique compuesto `(product_id, product_variant_id)`.

Una fila con `product_variant_id = null` representa el stock del producto sin variante. Cada variante con stock propio obtiene su propia fila.

Nota sobre el índice único: en MySQL los `NULL` no se consideran iguales entre sí, así que la unicidad no se aplica a las filas de producto base. Por eso la garantía de "exactamente una fila de stock por producto" la sostiene la aplicación, no el índice: la creación de la fila de stock ocurre siempre dentro de la misma transacción que la creación del producto, y `ProductStock::forProduct()` usa `firstOrCreate`. El índice único sí protege el caso de variantes.

La migración copia los valores actuales desde `products.stock_quantity`, `products.min_stock_alert` y `products.stock_type` para no perder datos del seed existente.

### 4.4 Tabla `stock_movements`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | |
| `product_stock_id` | foreignId | FK → `product_stocks`, cascadeOnDelete |
| `type` | enum(`in`,`out`,`adjustment`) | |
| `quantity` | decimal(12,2) | cantidad del movimiento |
| `reason` | string nullable | |
| `user_id` | foreignId nullable | FK → `users`, nullOnDelete |
| timestamps | | | |

Es el historial de auditoría. No se expone UI completa de movimientos en este alcance; la tabla queda lista y se escribe desde cada ajuste.

### 4.5 Modelos

- `Product`: usa `SoftDeletes`. `$fillable` sin las columnas de stock. Nueva relación `stock()` → `hasOne(ProductStock::class)`. `isLowStock()` se conserva pero delega en la relación: devuelve `false` cuando no hay fila de stock, y en otro caso compara `stock->quantity` contra `stock->min_alert`.
- `ProductVariant`: `$fillable` sin `stock_quantity`. Nueva relación `stock()` → `hasOne(ProductStock::class)`.
- `ProductStock` (nuevo): `product()`, `variant()`, `movements()`. Helper `status()` que devuelve `critical` | `low` | `normal` comparando `quantity` contra `min_alert`.
- `StockMovement` (nuevo): `stock()` → belongsTo.

### 4.6 Migración de datos

El seeder existente se actualiza para crear las filas de `product_stocks` correspondientes en lugar de escribir en `products`. Los productos del seed se replican como filas de stock (producto sin variante) y cada variante creada genera su propia fila.

---

## 5. API

### 5.1 Productos — `ProductController`

| Método | Ruta | Cambio |
|---|---|---|
| `index` | `GET /api/v1/products` | Quita el filtro `low_stock`. Eager-loads `category`, `variants`, `stock`. |
| `store` | `POST /api/v1/products` | Quita `stock_quantity`, `min_stock_alert`, `stock_type` de la validación. Acepta `image` (string, la ruta ya subida) e `is_active`. |
| `show` | `GET /api/v1/products/{id}` | Eager-loads `category`, `variants`, `stock`. |
| `update` | `PUT /api/v1/products/{id}` | Igual que store. Las variantes se reescriben solo si vienen en el payload. |
| `destroy` | `DELETE /api/v1/products/{id}` | Borrado lógico. |
| `toggleActive` | `PATCH /api/v1/products/{id}/toggle-active` | Alterna `is_active`. |
| `uploadImage` | `POST /api/v1/products/{id}/image` | `multipart/form-data`, valida `image` como `mimes:jpg,jpeg,png,webp`, `max:2048`. Reemplaza el archivo anterior. |
| `store`/`update` de variantes | anidados | `variants.*.stock_quantity` sale de la validación. |

La ruta `POST /products/{product}/adjust-stock` se elimina de `ProductController`.

### 5.2 Inventario — `InventoryController` (nuevo)

| Método | Ruta | Rol |
|---|---|---|
| `index` | `GET /api/v1/inventory` | admin |
| `lowStock` | `GET /api/v1/inventory/low-stock` | admin |
| `adjust` | `POST /api/v1/inventory/{stock}/adjust` | admin |

`index` acepta `search`, `category_id`, `status` (`normal`\|`low`\|`critical`) y `page`/`per_page`. Devuelve `stock` con `product.category` y `variant`, más un bloque `meta.stats` con `total_products`, `total_units`, `low_count`, `critical_count`.

`adjust` valida `new_quantity` (numeric, min 0), `type` (in|out|adjustment) y `reason` (required, max 255). Calcula el delta contra la cantidad actual, actualiza `quantity` y escribe la fila en `stock_movements` con el `user_id` del token.

### 5.3 Categorías

`CategoryController` no cambia. La tabla `categories` ya tiene la columna `icon` (string nullable) desde `2026_09_26_192001_create_categories_table.php:15`, y el seeder ya la puebla con nombres lucide en kebab-case (`ice-cream`, `glass`, `box`, `coffee`, `sparkles`). No hay que cambiar esquema ni datos de categorías.

Como `lucide-vue-next` no resuelve cadenas arbitrarias a componentes, el frontend mantiene un mapa explícito en `resources/js/config/categoryIcons.js` que traduce el nombre de la categoría a un componente lucide importado estáticamente, con `IceCreamBowl` como fallback para cualquier categoría sin entrada. Esto mantiene el bundle estático y evita `import()` dinámico.

---

## 6. Servicios

`InventoryService` conserva su firma pública exacta para que `OrderService` no cambie:

```php
deductStock(Product $product, ?ProductVariant $variant, float $quantity): void
restoreStock(Product $product, ?ProductVariant $variant, float $quantity): void
adjustStock(Product $product, ?ProductVariant $variant, float $newQuantity, string $reason = ''): void
getLowStockProducts(): Collection
```

Por dentro las cuatro operan sobre `product_stocks`. `adjustStock` además escribe en `stock_movements`. `getLowStockProducts` pasa a consultar `product_stocks` en lugar de comparar columnas de `products`.

Tolerancia a fila de stock ausente: `deductStock` y `restoreStock` resuelven la fila con `firstOrCreate` sobre `(product_id, product_variant_id)`. Esto es obligatorio, no opcional: `ApiEndpointsTest::test_order_and_invoice_checkout_api_flow` crea el producto con `Product::create([... 'stock_quantity' => 30])` y, al dejar de ser un campo rellenable, esa fila de stock nunca se crea. Si `deductStock` hiciera `findOrFail` sobre una fila inexistente, ese pedido devolvería 500.

`OrderService`, `ReportService`, `DianInvoiceProvider`, `InternalInvoiceProvider` y `OrderItem` no se modifican. Verificado que ninguno lee `stock_quantity` directamente: todos pasan por `InventoryService`.

---

## 7. Frontend

### 7.1 Stores Pinia

`stores/products.js`:

- Estado: `items`, `categories`, `loading`, `saving`, `error`, `imageUploading`
- Getter `activeItems` (filtra `is_active` y `deleted_at`)
- Acciones: `fetchProducts(filtros)`, `fetchCategories()`, `createProduct(datos)`, `updateProduct(id, datos)`, `deleteProduct(id)`, `toggleActive(id)`, `uploadImage(id, archivo)`

`stores/inventory.js`:

- Estado: `items`, `stats`, `loading`, `saving`, `error`
- Acciones: `fetchInventory(filtros)`, `fetchLowStock()`, `adjustStock(id, datos)`

`stores/toast.js`: `push(tipo, mensaje)`, `success()`, `error()`, `dismiss()`.

### 7.2 Vista Productos

Estructura:

1. `PageHeader` — título "Productos", subtítulo "Catálogo de helado, bebidas y postres", acción `AppButton primary` con icono `Plus`, texto "Nuevo producto".
2. Card blanca: buscador con icono `Search` (filtra por nombre y descripción), `AppSelect` de categoría, `AppSelect` de estado (Todos / Activos / Inactivos), contador de resultados.
3. `AppTable` con columnas **Imagen, Nombre, Categoría, Precio, Estado, Acciones**.
4. `AppPagination` con "Mostrando X-Y de Z registros".

Detalles de celda:

- **Imagen**: si existe `image`, `<img>` de 40x40 con `rounded-lg object-cover`; si no, `AppIcon` con el icono lucide que resuelve `categoryIcons.js` para la categoría del producto, dentro de un cuadro de 40x40 con fondo `slate-100`.
- **Nombre**: `font-semibold text-slate-800` con la descripción debajo en `text-xs text-slate-400`. Si el producto tiene variantes, un badge neutro con el conteo.
- **Categoría**: `AppBadge neutral`.
- **Precio**: `font-bold` con formato `es-CO` (`$6.000`).
- **Estado**: `AppBadge success` "Activo" o `AppBadge warning` "Inactivo".
- **Acciones**: `Pencil` editar y `Trash2` eliminar, como botones de icono de 32px con tooltip. El de eliminar en `rose-600` al hover. Al eliminar se abre `ConfirmDialog`.

Filas con `hover:bg-slate-50 transition-colors`. El stock no aparece en ningún punto de esta vista.

`ProductFormModal.vue` en dos pestañas:

- **Datos**: drag & drop de imagen con preview, nombre, categoría, descripción, precio de venta, precio de costo, switch "Activo". El margen se calcula en vivo a partir de costo y venta. La imagen y el nombre son obligatorios.
- **Variantes**: lista editable con nombre, costo y venta por variante; botones para agregar y quitar.

Al guardar, los errores 422 del backend se mapean campo por campo y se muestran bajo el input correspondiente mediante la prop `error` de cada primitivo. El modal se cierra solo tras un `201`/`200` exitoso y dispara un toast.

### 7.3 Vista Inventario

Estructura:

1. `PageHeader` — título "Inventario", subtítulo "Existencias por producto y variante", acción `AppButton primary` con icono `Plus`, texto "Registrar entrada".
2. Fila de 4 `StatCard`: Total productos en inventario (`Boxes`), Unidades en stock (`Package`), Stock bajo (`AlertTriangle`), Stock crítico (`AlertOctagon`).
3. Card blanca: buscador, `AppSelect` de categoría, `AppSelect` de estado (Todos / Normal / Bajo / Crítico).
4. `AppTable` con columnas **Producto, Categoría, Variante, Cantidad, Stock mínimo, Estado, Acciones**.
5. `AppPagination`.

Detalles de celda:

- **Cantidad**: número en negrita con su unidad — `25` para `unit`, `1.5 kg` para `bulk_grams`.
- **Estado**: `AppBadge success` "Normal", `AppBadge warning` "Bajo", `AppBadge danger` "Crítico".
- **Variante**: nombre de la variante o `AppBadge neutral` "Producto base".
- **Acciones**: `SlidersHorizontal` abre `StockAdjustModal`.

Los productos en estado crítico se ordenan primero y sus filas llevan un fondo `rose-50/40` sutil.

`StockAdjustModal.vue` muestra el producto y la variante, la cantidad actual, un input para la nueva cantidad, un `AppSelect` de tipo de movimiento (Entrada / Salida / Ajuste) y un campo de motivo obligatorio. Al guardar llama `adjustStock` y muestra toast.

### 7.4 Rutas

`/inventario` con nombre `inventory`, `meta: { requiresAuth: true, roles: ['admin'] }`.

### 7.5 Migración de las otras vistas

`Sidebar.vue` actual y el layout de `App.vue` se reemplazan. `PosView`, `CashRegisterView`, `CheckoutView`, `ReportsView`, `SettingsView` y `LoginView` siguen funcionando con su markup actual; se adaptarán al sistema de diseño en un lote posterior. Los emojis que contienen se reemplazan por iconos lucide en ese lote, no en este.

---

## 8. Verificación

**Este proyecto no mantiene pruebas automatizadas.** El directorio `tests/` se eliminó por decisión del usuario: la verificación se hace de forma manual sobre la aplicación en marcha. No se crean pruebas nuevas.

### 8.1 Qué hay que comprobar a mano

- `php artisan migrate` aplica limpio sobre la base de desarrollo y `migrate:rollback` revierte sin dejar el esquema a medias. El ciclo rollback y migrate debe repetirse varias veces sin errores.
- `php artisan db:seed` corre sin error y deja 9 productos, 8 variantes y 17 filas en `product_stocks` (9 de producto base y 8 de variante).
- `GET /api/v1/products` no devuelve `stock_quantity`, `min_stock_alert` ni `stock_type` en el JSON. Comprobarlo sobre el JSON crudo y no sobre la UI: un fallo de relación Eloquent se manifiesta como `null` silencioso, y la UI lo disimula mostrando "Producto base" en todas las filas.
- `POST /api/v1/products` funciona sin campos de stock; los que lleguen de más se descartan sin error.
- `POST /api/v1/inventory/{id}/adjust` cambia la cantidad y escribe una fila en `stock_movements`. Sin `reason` debe devolver 422.
- Un `cashier` recibe 403 en las rutas de inventario.
- Crear un pedido descuenta stock vía `product_stocks`; cancelarlo lo restituye.
- La columna Variante de Inventario muestra el nombre real de cada variante. Si todas las filas dicen "Producto base", la relación `ProductStock::variant()` perdió su clave foránea.
- Borrar un producto que ya se vendió deja su pedido y su factura intactos.
- `npm run build` compila sin errores.
- Ninguna vista del alcance contiene un emoji.

### 8.2 Nota sobre la base de datos

La contraseña de los usuarios del seed es `password`. `php artisan migrate:fresh --seed` es necesario para recuperar los datos de demostración después de cualquier cambio de esquema.

---

## 9. Riesgos

| Riesgo | Mitigación |
|---|---|
| La migración toca el flujo de ventas | `InventoryService` conserva su firma; `OrderService` y los servicios de facturación no se modifican |
| Pérdida de datos al mover el stock | La migración copia los valores existentes antes de eliminar las columnas |
| Borrado accidental de historial de ventas | `SoftDeletes` en productos + `nullOnDelete` en `order_items.product_id` |
| Fotos huérfanas al reemplazar una imagen | Se elimina el archivo anterior al guardar el nuevo |
| Regresión en POS por cambio de contrato | El JSON de productos conserva `variants` y los precios; solo se retiran los campos de stock |
