# Especificación de Diseño: ERP para Heladería (Laravel 11 + Vue 3 SPA)

**Fecha:** 2026-09-26  
**Estado:** Aprobado para Planificación  
**Autor:** Senior Full Stack Architect  

---

## 1. Visión General del Sistema
El sistema es un ERP integral diseñado para la gestión operativa y financiera de una heladería de local único. Provee control centralizado de catálogo de productos (helados, sabores, toppings, combos) con variantes y stock, gestión interactiva de mesas y pedidos (comedor y para llevar), facturación flexible (consecutivo interno en PDF térmico y arquitectura desacoplada para DIAN Colombia UBL 2.1), control de turnos y arqueos de caja, y reportes analíticos de rentabilidad y ventas.

---

## 2. Arquitectura del Sistema

### 2.1 Stack Tecnológico
- **Backend**: Laravel 11.x, PHP 8.2+
- **Base de Datos**: MySQL 8.x
- **Frontend**: Vue 3 (Composition API `<script setup>`), Vite, Tailwind CSS
- **Enrutamiento y Estado**: Vue Router 4, Pinia
- **Autenticación**: Laravel Sanctum (Tokens Bearer para API REST)
- **Roles y Permisos**: `spatie/laravel-permission`
- **Generación de Documentos**: `barryvdh/laravel-dompdf` (PDF formato ticket térmico 80mm y carta)
- **Gráficas / Visualización**: Chart.js con `vue-chartjs`

### 2.2 Patrón de Despliegue y Organización de Código
**Monolito Híbrido SPA**:
- Laravel aloja el backend y expone una API REST pura bajo el prefijo `/api/v1/`.
- La Single Page Application (SPA) de Vue 3 reside en `resources/js/` y es compilada mediante Vite hacia `public/build`.
- Una ruta catch-all en Laravel sirve la plantilla Blade base (`resources/views/app.blade.php`), garantizando cero inconvenientes de CORS y facilitando despliegues con un solo comando.

### 2.3 Capas Backend
- **Controllers Delgados**: Invocan Form Requests de validación y delegan la ejecución a Servicios.
- **Form Requests**: Validación exhaustiva de entradas (tipos, reglas de negocio, mensajes en español).
- **Services de Dominio**:
  - `OrderService`: Gestión del ciclo de vida de pedidos, cálculo de totales, sincronización de estados de mesas.
  - `InventoryService`: Control de existencias, deducción por venta, alertas de stock mínimo.
  - `BillingService`: Orquestación de facturas, métodos de pago múltiples, llamado al proveedor de facturación.
  - `CashRegisterService`: Apertura de turnos, arqueos, control de egresos e ingresos, diferencias de caja.
  - `ReportService`: Consolidación de métricas de rentabilidad, ventas por período y productos estrella.
- **API Resources**: Formato estándar de respuestas JSON:
  ```json
  {
    "status": "success",
    "message": "Mensaje informativo",
    "data": {}
  }
  ```

---

## 3. Modelo de Datos y Entidades (MySQL 8)

### 3.1 Usuarios y Seguridad
- **`users`**: `id`, `name`, `email`, `password`, `is_active` (boolean), `created_at`, `updated_at`.
- **Tablas Spatie**: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.
  - Roles iniciales: `admin`, `cashier`, `waiter`, `kitchen`.

### 3.2 Catálogo e Inventario
- **`categories`**: `id`, `name`, `slug`, `icon`, `is_active` (boolean), timestamps.
- **`products`**: `id`, `category_id` (FK), `name`, `description`, `cost_price` (decimal 12,2), `sale_price` (decimal 12,2), `stock_type` (`unit`, `bulk_grams`), `stock_quantity` (decimal 12,2), `min_stock_alert` (decimal 12,2), `has_variants` (boolean), `is_active` (boolean), timestamps.
- **`product_variants`**: `id`, `product_id` (FK), `name` (ej. "Vaso 1 Bola", "Copa 3 Bolas", "Litro"), `cost_price` (decimal 12,2), `sale_price` (decimal 12,2), `stock_quantity` (decimal 12,2), `is_active` (boolean), timestamps.

### 3.3 Mesas y Pedidos
- **`tables`**: `id`, `number` (string/int único), `name` (ej. "Mesa 1", "Barra Principal"), `capacity` (int), `status` (`available`, `occupied`, `reserved`), timestamps.
- **`orders`**: `id`, `order_number` (string único tipo `ORD-20260926-0001`), `table_id` (FK, nullable), `user_id` (FK), `type` (`dine_in`, `takeaway`), `status` (`open`, `in_kitchen`, `delivered`, `closed`, `cancelled`), `notes` (text nullable), `subtotal` (decimal 12,2), `tax_total` (decimal 12,2), `discount_total` (decimal 12,2), `tip_amount` (decimal 12,2), `total` (decimal 12,2), timestamps.
- **`order_items`**: `id`, `order_id` (FK), `product_id` (FK), `product_variant_id` (FK nullable), `quantity` (decimal 8,2), `unit_price` (decimal 12,2), `subtotal` (decimal 12,2), `notes` (text nullable, ej. "Sabores: Vainilla y Fresa, sin chispas"), timestamps.

### 3.4 Facturación y Pagos
- **`invoices`**: `id`, `order_id` (FK), `invoice_number` (string único consecutivo, ej. `FAC-0001`), `customer_name` (string default "Consumidor Final"), `customer_doc_type` (string default "CC"), `customer_doc_number` (string default "222222222222"), `subtotal` (decimal 12,2), `tax_amount` (decimal 12,2), `discount_amount` (decimal 12,2), `tip_amount` (decimal 12,2), `total` (decimal 12,2), `billing_mode` (`internal`, `dian`), `dian_cufe` (string nullable), `dian_status` (`pending`, `accepted`, `rejected`), `pdf_path` (string), timestamps.
- **`payments`**: `id`, `invoice_id` (FK), `payment_method` (`cash`, `card`, `transfer`), `amount` (decimal 12,2), `reference_code` (string nullable), timestamps.

### 3.5 Caja y Finanzas
- **`cash_registers`**: `id`, `user_id` (FK), `opened_at` (datetime), `closed_at` (datetime nullable), `opening_balance` (decimal 12,2), `expected_balance` (decimal 12,2 nullable), `actual_balance` (decimal 12,2 nullable), `difference` (decimal 12,2 nullable), `status` (`open`, `closed`), `notes` (text nullable), timestamps.
- **`cash_movements`**: `id`, `cash_register_id` (FK), `user_id` (FK), `type` (`cash_in`, `cash_out`), `category` (`operating_expense`, `purchase`, `payroll`, `other`), `amount` (decimal 12,2), `description` (text), timestamps.

### 3.6 Parámetros Generales
- **`business_settings`**: `key` (string primary), `value` (text nullable), timestamps.
  - Parámetros: nombre_comercial, nit, direccion, telefono, email, resolucion_facturacion, prefijo_factura, consecutivo_actual, iva_porcentaje, propina_sugerida_porcentaje, billing_mode.

---

## 4. Arquitectura de Facturación (Estrategia DIAN / Interna)

### 4.1 Contrato del Proveedor
```php
namespace App\Contracts;

use App\Models\Invoice;

interface InvoiceProviderInterface
{
    public function generateInvoice(Invoice $invoice): array;
    public function checkStatus(string $invoiceId): string;
    public function cancelInvoice(Invoice $invoice, string $reason): bool;
}
```

### 4.2 Implementaciones
1. **`InternalInvoiceProvider`**:
   - Asigna número consecutivo correlativo (`FAC-XXXX` o `TICK-XXXX`).
   - Renderiza vista Blade en PDF (`resources/views/pdf/ticket.blade.php`) diseñada específicamente para ancho térmico de 80mm.
   - Guarda el PDF en `storage/app/public/invoices/` y retorna la URL pública de descarga/impresión directa.
2. **`DianInvoiceProvider`**:
   - Construye el payload UBL 2.1 exigido por la DIAN (adquirente, emisor, líneas de detalle con códigos estándar, base imponible e impuestos).
   - Genera la estructura criptográfica de CUFE (Código Único de Facturación Electrónica mediante SHA-384).
   - Deja lista la integración vía cliente HTTP de Laravel para consumir cualquier API de proveedor tecnológico habilitado (Factus, Siigo, Alegra) mediante credenciales configuradas en `.env` (`DIAN_API_URL`, `DIAN_API_TOKEN`).

---

## 5. Diseño del Frontend (Vue 3 + Pinia + Tailwind)

### 5.1 Enrutamiento y Protección
- `/login`: Autenticación y recuperación.
- `/pos`: Punto de Venta con cuadrícula de mesas interactivas, estados por color (Verde = Libre, Ámbar = Ocupada, Azul = Reservada) y acceso a pedido para llevar.
- `/pedidos`: Monitoreo y actualización de pedidos activos.
- `/cobro/:orderId`: Pantalla de liquidación, división de cuentas, desglose de pagos múltiples y emisión de factura.
- `/productos`: Catálogo, categorías, control de variantes de helado y alertas de inventario bajo.
- `/caja`: Control de sesión de caja, arqueo ciego/abierto, ingresos/egresos y reporte de cierre.
- `/reportes`: Panel analítico financiero con KPIs y gráficos de ventas/gastos.
- `/configuracion`: Ajustes del establecimiento y parámetros fiscales.

### 5.2 Stores de Pinia
- `auth`: Estado de sesión, token Sanctum, roles, verificación reactiva de permisos.
- `tables`: Listado y mutaciones del estado de las mesas.
- `orders`: Comanda actual en edición, cálculo instantáneo de subtotales, impuestos y propinas.
- `cash`: Estado del turno de caja del usuario activo.
- `toast`: Notificaciones visuales flotantes accesibles globalmente.

---

## 6. Pruebas y Criterios de Aceptación

### 6.1 Pruebas Automatizadas (Feature Tests)
- `AuthenticationTest`: Validación de login, emisión de tokens Sanctum y protección de rutas según rol.
- `ProductInventoryTest`: CRUD de productos, manejo de variantes y decremento de stock al registrar pedidos.
- `OrderFlowTest`: Ciclo de vida de una orden en mesa (apertura, adición de items, cambio a ocupada, finalización).
- `BillingTest`: Facturación con consecutivo interno consecutivo, soporte de pagos múltiples y generación física del archivo PDF de ticket.
- `CashRegisterTest`: Apertura de turno, movimientos de caja menor, cierre y cálculo exacto de diferencia.

### 6.2 Criterios de Calidad
- Migraciones ejecutables sin errores con integridad referencial.
- Seeders con datos iniciales coherentes (4 usuarios con roles específicos, categorías, helados y mesas).
- Compilación de assets frontend con Vite sin warnings ni errores.
