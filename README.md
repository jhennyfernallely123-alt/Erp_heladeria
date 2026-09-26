# ERP Heladería Nieve Real (Laravel 11 + Vue 3 SPA + MySQL 8)

Sistema integral tipo ERP y Punto de Venta (POS) para heladería de local único, diseñado con arquitectura limpia desacoplada (Laravel API REST + Vue 3 SPA con Pinia y Tailwind CSS).

---

## 1. Requisitos del Sistema
- **PHP**: 8.2 o superior (probado en PHP 8.5)
- **Composer**: 2.x
- **Node.js**: 18+ y NPM
- **Base de Datos**: MySQL 8.x o MariaDB 10.4+
- **Extensiones PHP**: `pdo_mysql`, `bcmath`, `gd` o `imagick`, `mbstring`, `fileinfo`

---

## 2. Instrucciones de Instalación Rápida

### Paso 1: Clonar o posicionarse en el proyecto
```bash
cd erp
```

### Paso 2: Instalar dependencias de PHP y Node
```bash
composer install
npm install
```

### Paso 3: Configurar variables de entorno (.env)
Copiar el archivo de configuración:
```bash
copy .env.example .env
php artisan key:generate
```

Asegurar los parámetros de conexión en `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=icecream_erp
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_unicode_ci

# Facturación: 'internal' para ticket térmico DomPDF o 'dian' para facturación electrónica Colombia
BILLING_MODE=internal
DIAN_API_URL=https://api.dian.gov.co/mock
DIAN_API_TOKEN=mock_token_123
```

Crear el enlace de almacenamiento simbólico para los PDFs de facturas:
```bash
php artisan storage:link
```

### Paso 4: Ejecutar Migraciones y Datos de Prueba (Seeders)
```bash
php artisan migrate:fresh --seed
```

### Paso 5: Compilar el Frontend o Servir en Desarrollo
Para producción:
```bash
npm run build
```

Para desarrollo con Hot-Reload (Vite):
```bash
# Terminal 1: Servir Laravel
php artisan serve

# Terminal 2: Vite Dev Server
npm run dev
```

---

## 3. Usuarios y Credenciales Preconfiguradas

Todos los usuarios cuentan con la contraseña genérica: `password`

| Rol | Correo Electrónico | Contraseña | Permisos Principales |
|---|---|---|---|
| **Administrador** | `admin@heladeria.com` | `password` | Acceso total: POS, Catálogo, Caja, Finanzas/Reportes, Configuración DIAN |
| **Cajero** | `cajero@heladeria.com` | `password` | POS, Cobro/Facturación, Apertura/Cierre de Turnos de Caja Menor, Inventario |
| **Mesero** | `mesero@heladeria.com` | `password` | Mapa de Mesas del Salón, Toma y Edición de Comandas, Venta para Llevar |
| **Cocina / Barra** | `cocina@heladeria.com` | `password` | Monitoreo de pedidos en cocina y cambio de estado a entregado |

---

## 4. Módulos y Características

### A. Autenticación y Permisos
- Tokens Bearer con **Laravel Sanctum**.
- Roles y permisos granulares con **Spatie Laravel-Permission** (`admin`, `cashier`, `waiter`, `kitchen`).

### B. Catálogo e Inventario
- Categorías (Helados tradicionales, gourmet, copas especiales, bebidas, potes y toppings).
- Productos con variantes de tamaño (1 bola, 2 bolas, 3 bolas, 1/2 litro, 1 litro).
- Alertas visuales de stock bajo según umbral mínimo (`min_stock_alert`).
- Descuento automático de stock al confirmar comanda y reversión automática si se cancela.

### C. Mesas y Comandas (POS)
- Mapa visual de salón (8 mesas preconfiguradas con capacidades y código de colores: Verde = Libre, Naranja = Ocupada, Azul = Reservada).
- Drawer lateral de comanda con catálogo por categorías y notas por producto.
- Acceso rápido para "Venta para Llevar / Mostrador" (sin mesa asignada).

### D. Facturación Dual (Interna & DIAN UBL 2.1)
- **Modo Interno (`internal`)**: Consecutivo automático correlativo (`FAC-0001`), renderizado de ticket térmico en PDF de 80mm con **DomPDF**, descarga e impresión directa.
- **Modo DIAN (`dian`)**: Cálculo criptográfico de **CUFE** (SHA-384), estructura UBL 2.1 con emisor, adquirente y líneas formateadas, listo para conectar a API de proveedores tecnológicos autorizados (Factus, Siigo, Alegra) a través de `DianInvoiceProvider`.
- Medios de pago múltiples (efectivo con calculadora de cambio, tarjetas, transferencias) y división equitativa de cuenta.

### E. Turnos de Caja & Arqueo
- Apertura con saldo base en gaveta.
- Registro de gastos menores operativos, compras urgentes de insumos y nómina.
- Cierre ciego/supervisado con cálculo automático de diferencia (faltante o sobrante).

### F. Analítica y Reportes Financieros
- KPIs de ventas totales, ticket promedio, margen bruto estimado (ventas - costos de mercadería) y flujo neto.
- Ranking de los 5 helados/productos más vendidos por volumen y recaudación.
- Desglose gráfico por método de pago.

---

## 5. Pruebas Automatizadas
Ejecuta la suite completa de Feature Tests:
```bash
php artisan test
```
Incluye pruebas para:
- `AuthenticationTest`: Validación de login y protección de rutas.
- `ProductInventoryTest`: Manejo de variantes y cálculo de stock.
- `OrderFlowTest`: Ciclo de vida y transición de mesas.
- `InvoicingProviderTest`: Generación de PDF térmico y cálculo de CUFE DIAN.
- `CashRegisterTest`: Balance de apertura, gastos y cálculo de descuadre en cierre.
