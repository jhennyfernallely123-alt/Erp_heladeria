# Especificación de Diseño: Domicilios

**Fecha:** 2026-09-27
**Estado:** Aprobado por el usuario
**Alcance:** ventas a domicilio con dirección y teléfono, y tarifa fija de envío

---

## 1. Objetivo

El salón tiene hoy tres formas de atender: mesa (`dine_in`), para llevar
(`takeaway`) y, desde el commit `2026_09_27_100003`, 8 asientos de barra
independientes. Falta el **domicilio**: una venta que sale del local y se
entrega en una dirección.

El domiciliario necesita tres cosas para llevar el pedido: **a quién**, **a
dónde** y **cómo llegarle**. Y el cliente necesita saber cuánto cuesta el envío.

Lo que se agrega:

- Un tercer tipo de pedido, `delivery`.
- Los datos de entrega capturados **al tomar el pedido**, no al cobrar.
- Una tarifa de envío fija, configurable, que se congela en el pedido.
- Los datos de entrega impresos en el ticket de 80 mm y en la factura A4.

---

## 2. Decisiones tomadas

| Decisión | Valor | Motivo |
|---|---|---|
| Costo de envío | Tarifa fija configurable | Es lo habitual en heladería. Lo pidió el usuario. |
| Momento de capturar | Al tomar el pedido | El domicillero ya sabe a dónde llevar desde el arranque. |
| Formato de dirección | Una línea libre + referencias | Menos campos que llenar; el domicillero escribe como habla. |
| Campos obligatorios | Nombre + teléfono + dirección | Sin teléfono no se avisa la llegada; sin dirección no hay entrega. |
| Seguimiento del envío | No | El usuario no quiere tablero de pedidos. |
| Quién puede tomar un domicilio | `admin` y `cajero` | El domicilio entra por teléfono, y quien atiende el teléfono en el salón es el cajero o el admin. El mesero trabaja las mesas, no la calle. |
| Almacenamiento de la tarifa | Snapshot en `orders.delivery_fee` | Un precio es un snapshot. Si la tarifa sube mañana, el pedido de hoy conserva lo que se cobró. |

---

## 3. Modelo de datos

### 3.1 Migración

`database/migrations/2026_09_27_100004_add_delivery_to_orders.php`

```php
// enum 'type': agregar 'delivery'
$table->string('delivery_name')->nullable();
$table->string('delivery_phone')->nullable();
$table->string('delivery_address')->nullable();
$table->text('delivery_notes')->nullable();
$table->decimal('delivery_fee', 12, 2)->default(0.00);
```

**Por qué en `orders` y no en `invoices`:** la dirección se necesita antes de
facturar. Si viviera en `invoices` no existiría hasta el momento del cobro, que
es tarde para organizar la entrega.

**Cómo llega a la factura:** para un pedido `delivery`, `InvoiceController`
pisa los datos del cliente con los del pedido, sin importar lo que mande el
formulario:

```
customer_name  ← order.delivery_name
customer_phone ← order.delivery_phone
```

Motivo: el cajero no debe poder escribir a mano un nombre distinto al que va
impreso en la etiqueta de la caja, y la factura tiene que quedar con el mismo
contacto que la comanda. La dirección **no** se copia a `invoices`: se imprime
desde `order.delivery_address` en el momento de renderizar el documento.

**Reversibilidad:** el `down()` quita las cinco columnas y vuelve el enum a
`['dine_in', 'takeaway']`. Es seguro porque las columnas nuevas son nullable y
`delivery_fee` tiene default.

### 3.2 Los cinco campos de entrega

| Campo | Tipo | Uso | Ejemplo |
|---|---|---|---|
| `delivery_name` | string | A quién se entrega | `María López` |
| `delivery_phone` | string | Para llamar al llegar | `300 123 4567` |
| `delivery_address` | string | Dirección completa en una línea | `Cra 45 # 12-34, Apto 501` |
| `delivery_notes` | text | Referencias para llegar | `Timbre en la portería, portón azul` |
| `delivery_fee` | decimal(12,2) | Tarifa congelada al crear el pedido | `3000.00` |

### 3.3 Parámetro configurable

Nueva clave en `business_settings`: `delivery_fee` (decimal en pesos).

Se edita en la pantalla de Configuración, junto a los datos fiscales. Valor
inicial sugerido: `3000.00`.

### 3.4 Permiso nuevo

`take_deliveries`, agregado en `RolesAndUsersSeeder` y asignado a `admin` y
`cashier`. Para aplicarlo en la base que ya está corriendo se reejecuta solo ese
seeder, que es idempotente (los permisos y roles usan `firstOrCreate` y los
roles se sincronizan con `syncPermissions`):

```bash
php artisan db:seed --class=RolesAndUsersSeeder
```

Los cuatro usuarios de prueba conservan sus credenciales porque el seeder usa
`firstOrCreate` también para usuarios.

---

## 4. Reglas de negocio

### 4.1 Validación de creación

`OrderController::store()`:

- `type` acepta `in:dine_in,takeaway,delivery`.
- Si `type === 'delivery'`:
  - `delivery_name` requerido, `string`, `max:120`
  - `delivery_phone` requerido, `string`, `max:30`
  - `delivery_address` requerido, `string`, `max:255`
  - `delivery_notes` opcional, `string`, `max:500`
- Si `type !== 'delivery'`: los cinco campos se guardan en `null` / `0.00`. Se
  ignoran aunque el cliente los mande.

Un domicilio **nunca** lleva `table_id`: el flujo de mesas y liberación de mesa
no aplica, igual que en `takeaway`.

### 4.2 Quién puede tomar un domicilio

Permiso nuevo: **`take_deliveries`**. Se le da a `admin` y a `cashier`. No lo
tienen `waiter` ni `kitchen`.

**El permiso se valida dentro de `OrderController::store()`, no como middleware
de ruta.** No se puede poner en la ruta porque crear un pedido de mesa o para
llevar es válido para todos los roles; lo restringido es **el valor
`type=delivery`**, no la acción de crear un pedido. Sería un chequeo
condicional:

```php
if (($data['type'] ?? null) === 'delivery'
    && !$request->user()->can('take_deliveries')) {
    return $this->errorResponse('No tienes permiso para tomar pedidos a domicilio.', 403);
}
```

Va **antes** de la validación de campos, para que un mesero reciba 403 y no un
422 con los detalles de qué campo le falta.

**Solo al crear.** `OrderController::update()` no acepta `type`, así que el tipo
de un pedido es inmutable: un pedido de mesa no se puede convertir en domicilio
después. Por eso no hace falta repetir el chequeo en el update.

**Sobre editar un domicilio ya creado:** cualquier rol que pueda editar pedidos
puede cambiarle los items, igual que con una comanda de mesa. La dirección y el
teléfono no se editan desde `update()` en este trabajo. Si más adelante se
quiere que solo cajero y admin modifiquen pedidos de domicilio, se agrega un
chequeo en `update()` mirando `$order->type`.

### 4.3 Congelamiento de la tarifa

`OrderService::createOrder()` lee `delivery_fee` de `BusinessSetting` y lo escribe
en el pedido. A partir de ahí el valor no se vuelve a leer de Configuración
para ese pedido, ni al editarlo ni al cobrarlo ni al facturarlo.

### 4.4 Cálculo del total

El total del pedido queda:

```
total = max(0, subtotal - discount_total + tip_amount + tax_total + delivery_fee)
```

`delivery_fee` **no** se suma al `subtotal`. Se muestra como línea propia en el
desglose para que el cliente vea cuánto es el envío.

### 4.5 Fuente única del total

**El total se calcula en un solo lugar: `OrderService::syncItems()`.**

`InvoiceController::store()` deja de recalcular el total y pasa a usar
`$order->total`, que es el snapshot que ya guardó el pedido. Motivo: hoy la
fórmula está duplicada en `OrderService` y en `InvoiceController`, y esa
duplicación es exactamente lo que haría que un domicilio falle con el error
"la suma de los pagos no coincide con el total a pagar". Con una sola fórmula,
el cobro valida contra el mismo número que el pedido guardó.

El frontend mantiene su propio cálculo para el carrito en vivo
(`stores/orders.js`), que es una estimación hasta que se guarda el pedido. Al
guardar, manda el del servidor.

---

## 5. Interfaz

### 5.1 POS

Botón **"Domicilio"** en el encabezado, al lado de "Venta para Llevar /
Mostrador". Usa `lucide-vue-next` (por ejemplo `Bike` o `Truck`). Nunca emojis.

Al pulsarlo: `orderStore.initNewOrder(null, 'delivery')`.

El botón **solo se renderiza** si `authStore.can('take_deliveries')`. Con los
permisos actuales, el mesero ve "Venta para Llevar / Mostrador" pero no
"Domicilio".

### 5.2 Drawer de comanda (`OrderDrawer.vue`)

Cuando el pedido es `delivery`, el drawer muestra un bloque de **Datos de
entrega** arriba, antes del catálogo de productos:

- Nombre (required)
- Teléfono (required)
- Dirección (required, multilínea de 2 filas)
- Referencias (opcional)

Reusa `AppInput` y la paleta existente. El bloque solo se renderiza para
`delivery`; mesa y para llevar no cambian.

### 5.3 Guardado

El botón "Guardar Comanda" se bloquea si falta nombre, teléfono o dirección,
con un mensaje bajo los campos que diga qué falta. El mensaje se escribe en
español y sin emojis.

Al ser una venta sin mesa, el botón "Cobrar / Facturar" sigue oculto para el
mesero, según la regla de permisos ya establecida.

### 5.4 Carrito

`stores/orders.js` suma la tarifa al `total` en vivo mientras se arma el pedido,
para que el cajero vea el número final antes de guardar.

**De dónde sale la tarifa en el front:** de `GET /api/v1/settings`, que ya
devuelve todos los parámetros como un mapa `clave => valor`. El POS lo pide una
vez al montar y guarda `delivery_fee` en un ref. No hace falta un endpoint nuevo.
El valor es una **estimación** hasta que se guarda el pedido; el que manda es el
snapshot del servidor.

### 5.5 Pantalla de cobro

`CheckoutView.vue` no cambia estructuralmente. Muestra el desglose y ya incluye
`order.total`, que trae la tarifa. Se agrega una fila visible **"Domicilio"**
con el valor cuando el pedido es de tipo `delivery`, para que el desglose sea
legible en la pantalla de cobro.

---

## 6. Documentos

### 6.1 Ticket 80 mm (`resources/views/pdf/ticket.blade.php`)

Donde hoy dice `Tipo: Venta Mostrador / Para Llevar`, un `delivery` imprime:

```
Cliente:     María López
Teléfono:    300 123 4567
Dirección:   Cra 45 # 12-34, Apto 501
Referencias: Timbre en la portería, portón azul
```

De dónde lee cada valor, para que quede explícito y no haya dos fuentes:

| Dato impreso | Origen |
|---|---|
| Cliente | `$invoice->customer_name` (que ya viene del pedido) |
| Teléfono | `$invoice->customer_phone` (idem) |
| Dirección | `$invoice->order->delivery_address` |
| Referencias | `$invoice->order->delivery_notes` |
| Línea "Domicilio" | `$invoice->order->delivery_fee` |

Y en la tabla de totales, una fila nueva entre Subtotal y TOTAL:

```
Domicilio:  $3.000
```

**Restricción del motor:** este documento lo genera DOMPDF, que no soporta SVG
(AGENTS.md §10.8). Si se agrega un icono, debe ser PNG. Aquí no hace falta
ninguno.

### 6.2 Factura A4 (`resources/views/pdf/invoice.blade.php`)

En el bloque de cliente, cuando es `delivery`, se agregan las líneas Teléfono y
Dirección. En el desglose de totales, la fila "Domicilio".

Este documento lo genera Chromium, así que acá sí se podrían usar SVG si hicieran
falta.

### 6.3 Mesa y para llevar

Los datos de entrega se imprimen **solo** si el pedido es `delivery`. Mesa y
para llevar siguen mostrando exactamente lo que muestran hoy.

---

## 7. Fuera de alcance

Explícitamente **no** se hace en este trabajo:

- Pantalla de pedidos por entregar, ni tablero de cocina.
- Estados nuevos de pedido. `in_kitchen` y `delivered` ya existen en el enum y
  quedan sin uso, como están hoy.
- Tabla separada de deliveries: los cinco campos en `orders` alcanzan.
- Dirección estructurada (barrio, ciudad, zona) o tarifas por zona.
- Repartidor asignado, horarios de entrega ni seguimiento por SMS.
- Botón de WhatsApp para enviar el pedido al cliente.

---

## 8. Riesgos y puntos de atención

| Riesgo | Mitigación |
|---|---|
| El total se calculaba en 3 lugares y cualquier diferencia rompe el cobro | Se deja una sola fórmula (§4.5). |
| `delivery_fee` como texto de negocio | Se congela en el pedido, no se relee (§4.3). |
| La dirección queda en `orders` y la factura la copia | Es intencional: la dirección es operativa, la factura es el respaldo legal. |
| Los pedidos de `delivery` no aparecen en la cuadrícula del POS | Es correcto: esa cuadrícula es de mesas. Se llegan por el flujo de cobro. |
| Editar un pedido de domicilio | El fee congelado sobrevive a la edición: `syncItems` no lo recalcula desde Configuración. |
| **La tarifa es un precio y `POST /api/v1/settings` hoy no pide permiso** | Cualquier rol autenticado puede escribir en `business_settings`. Al agregar un parámetro de precio esto pasa de "dato del local" a "cuánto se cobra". **Recomendación: en este mismo trabajo se protege `POST /settings` con `permission:manage_settings`**, que solo tiene admin. El front solo necesita el `GET`, que sigue abierto. Requiere confirmación del usuario porque es parte del pendiente de permisos que ya se le reportó. |
| Un rol sin `take_deliveries` podría crear un domicilio por API si el chequeo se queda solo en el front | El chequeo va en el controller, que es la autoridad, además de esconder el botón. Verificado con token de mesero y de cocina (§9, pasos 7 y 8). |

---

## 9. Verificación

Sin tests automatizados (decisión del proyecto, AGENTS.md §1.4). La
verificación es:

1. Crear un pedido de domicilio por la API con los 3 campos y verificar que
   `total = subtotal + fee`.
2. Intentar crear un domicilio sin teléfono y verificar 422.
3. Crear un pedido `takeaway` mandando campos de entrega y verificar que se
   guardan en `null`.
4. Facturar el domicilio y verificar que el ticket y la factura traen los datos
   de entrega y la línea del envío.
5. Verificar que cambiar `delivery_fee` en Configuración no altera un pedido ya
   creado.
6. Verificar en pantalla que el botón de cobro sigue oculto para el mesero.
7. Con token de **mesero**, `POST /orders` con `type=delivery` debe devolver
   **403**; con token de **cocina**, igual. Con token de **cajero** y de
   **admin**, debe crear el pedido.
8. Verificar en pantalla que el botón "Domicilio" no aparece para el mesero ni
   para cocina, y sí para cajero y admin.
