# Ice Cream ERP System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete Ice Cream Shop ERP with Laravel 11 (API REST), MySQL 8, and Vue 3 SPA (Composition API, Pinia, Tailwind CSS), including roles & permissions, catalog with variants, table/orders POS, cash register sessions, PDF invoice generation, and DIAN electronic invoicing abstraction.

**Architecture:** Hybrid SPA Monolith where Laravel 11 serves the SPA index blade and exposes standard REST API endpoints under `/api/v1/`. Business logic is partitioned into dedicated domain Services, Form Requests, API Resources, and a Strategy Pattern for Invoicing (`InternalInvoiceProvider` and `DianInvoiceProvider`).

**Tech Stack:** PHP 8.2+, Laravel 11, MySQL 8, Spatie Laravel-Permission, Barryvdh Laravel-DomPDF, Vue 3, Vite, Pinia, Vue Router 4, Tailwind CSS, Chart.js.

---

## File Structure Overview

### Backend Core & Invoicing
- `app/Contracts/InvoiceProviderInterface.php`: Invoicing contract (generate, status, cancel).
- `app/Services/Invoicing/InternalInvoiceProvider.php`: Consecutively numbered internal ticket generator with DomPDF.
- `app/Services/Invoicing/DianInvoiceProvider.php`: Colombian DIAN UBL 2.1 electronic invoicing adapter structure.
- `app/Services/Invoicing/InvoiceService.php`: Provider resolver and invoicing orchestrator.
- `app/Services/OrderService.php`: Order lifecycle, table state transitions, items calculation.
- `app/Services/CashRegisterService.php`: Shift opening, movements, closure balance comparison.
- `app/Services/InventoryService.php`: Stock alert checking, stock deduction, manual adjustment.
- `app/Services/ReportService.php`: Metrics, sales aggregations, profit estimation.

### Database Migrations & Seeds
- `database/migrations/*`: Tables for users, roles, categories, products, product_variants, tables, orders, order_items, invoices, payments, cash_registers, cash_movements, business_settings.
- `database/seeders/DatabaseSeeder.php`: Master seeder orchestrating initial data.
- `database/seeders/RolesAndUsersSeeder.php`: Spatie roles (admin, cashier, waiter, kitchen) and test accounts.
- `database/seeders/IceCreamCatalogSeeder.php`: Categories (Helados, Bebidas, Toppings, Combos), products with variants, stock levels.
- `database/seeders/TablesSeeder.php`: 8 ice cream parlour tables.
- `database/seeders/BusinessSettingsSeeder.php`: NIT, commercial name, tax percentage, billing mode.

### API Controllers, Requests & Resources
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/CategoryController.php`
- `app/Http/Controllers/Api/ProductController.php`
- `app/Http/Controllers/Api/TableController.php`
- `app/Http/Controllers/Api/OrderController.php`
- `app/Http/Controllers/Api/InvoiceController.php`
- `app/Http/Controllers/Api/CashRegisterController.php`
- `app/Http/Controllers/Api/FinanceController.php`
- `app/Http/Controllers/Api/SettingController.php`

### Frontend (SPA under `resources/js/`)
- `resources/js/app.js`: Main Vue 3 app mounting Pinia, Router.
- `resources/js/router/index.js`: Navigation guards checking auth token and Spatie permissions.
- `resources/js/stores/auth.js`: Sanctum token, user role and permissions state.
- `resources/js/stores/tables.js`: Tables listing and live status.
- `resources/js/stores/orders.js`: Active cart / order creation.
- `resources/js/stores/cash.js`: Active register status.
- `resources/js/views/LoginView.vue`: Authentication screen.
- `resources/js/views/PosView.vue`: Interactive tables map & quick order drawer.
- `resources/js/views/CheckoutView.vue`: Payment settlement, split bill, ticket print.
- `resources/js/views/ProductsView.vue`: Catalog management, variants modal, low stock badges.
- `resources/js/views/CashRegisterView.vue`: Open/close register, cash in/out.
- `resources/js/views/ReportsView.vue`: KPIs and Chart.js dashboards.
- `resources/js/views/SettingsView.vue`: Business settings & fiscal toggles.

---

## Tasks

### Task 1: Scaffold Laravel 11 Project & Base Environment Configuration

**Files:**
- Create: Project root files (`composer.json`, `.env.example`, `.env`, `artisan`, `bootstrap/app.php`)
- Modify: `.env` (Database credentials and billing variables)

- [ ] **Step 1: Scaffold Laravel 11 in current directory**

Run:
```bash
composer create-project laravel/laravel:^11.0 temp_laravel --prefer-dist
```
And copy files to root (preserving git repository).

- [ ] **Step 2: Configure `.env` for MySQL and Invoicing**

Update `.env` with:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=icecream_erp
DB_USERNAME=root
DB_PASSWORD=

BILLING_MODE=internal
DIAN_API_URL=https://api.dian.gov.co/mock
DIAN_API_TOKEN=mock_token_123
```

- [ ] **Step 3: Require dependencies**

Run:
```bash
composer require spatie/laravel-permission:^6.0 barryvdh/laravel-dompdf:^3.0
```

- [ ] **Step 4: Verify artisan runs**

Run:
```bash
php artisan --version
```
Expected: `Laravel Framework 11.x.x`

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "chore: scaffold laravel 11 with spatie permission and dompdf"
```

---

### Task 2: Database Migrations for All Domain Modules

**Files:**
- Create: `database/migrations/2026_09_26_000001_create_categories_table.php`
- Create: `database/migrations/2026_09_26_000002_create_products_table.php`
- Create: `database/migrations/2026_09_26_000003_create_product_variants_table.php`
- Create: `database/migrations/2026_09_26_000004_create_tables_table.php`
- Create: `database/migrations/2026_09_26_000005_create_orders_and_items_tables.php`
- Create: `database/migrations/2026_09_26_000006_create_invoices_and_payments_tables.php`
- Create: `database/migrations/2026_09_26_000007_create_cash_registers_and_movements_tables.php`
- Create: `database/migrations/2026_09_26_000008_create_business_settings_table.php`

- [ ] **Step 1: Write migrations with foreign keys, indexes and nullable fields as specified**
- [ ] **Step 2: Run migrations with SQLite/MySQL to verify schema integrity**

Run:
```bash
php artisan migrate --force
```
Expected: Migrations created successfully.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/
git commit -m "feat(db): add migrations for ice cream erp entities"
```

---

### Task 3: Eloquent Models and Relationships

**Files:**
- Create: `app/Models/Category.php`
- Create: `app/Models/Product.php`
- Create: `app/Models/ProductVariant.php`
- Create: `app/Models/RestaurantTable.php`
- Create: `app/Models/Order.php`
- Create: `app/Models/OrderItem.php`
- Create: `app/Models/Invoice.php`
- Create: `app/Models/Payment.php`
- Create: `app/Models/CashRegister.php`
- Create: `app/Models/CashMovement.php`
- Create: `app/Models/BusinessSetting.php`
- Modify: `app/Models/User.php` (add `HasRoles`, `HasApiTokens` / Sanctum)

- [ ] **Step 1: Implement Models with complete casts, `$fillable` arrays, and relations**
- [ ] **Step 2: Write test to verify relationships and role attachments**
- [ ] **Step 3: Run test**
Run: `php artisan test --filter=ModelRelationshipTest`
Expected: PASS
- [ ] **Step 4: Commit**

```bash
git add app/Models/ tests/Feature/ModelRelationshipTest.php
git commit -m "feat(models): create eloquent models with relationships"
```

---

### Task 4: Invoicing Layer (Strategy Pattern for Internal vs DIAN) & DomPDF Template

**Files:**
- Create: `app/Contracts/InvoiceProviderInterface.php`
- Create: `app/Services/Invoicing/InternalInvoiceProvider.php`
- Create: `app/Services/Invoicing/DianInvoiceProvider.php`
- Create: `app/Services/Invoicing/InvoiceService.php`
- Create: `resources/views/pdf/ticket.blade.php`
- Test: `tests/Feature/InvoicingProviderTest.php`

- [ ] **Step 1: Write test for InternalInvoiceProvider generating PDF & consecutive**
- [ ] **Step 2: Implement Interface and Providers**
- [ ] **Step 3: Create 80mm thermal ticket Blade template with shop details and QR/CUFE placeholder**
- [ ] **Step 4: Run test**
Run: `php artisan test --filter=InvoicingProviderTest`
Expected: PASS and PDF generated in `storage/app/public/invoices/`
- [ ] **Step 5: Commit**

```bash
git add app/Contracts/ app/Services/Invoicing/ resources/views/pdf/ tests/Feature/InvoicingProviderTest.php
git commit -m "feat(invoicing): implement strategy pattern for internal and DIAN providers"
```

---

### Task 5: Business Services (Order, Inventory, CashRegister, Report)

**Files:**
- Create: `app/Services/OrderService.php`
- Create: `app/Services/InventoryService.php`
- Create: `app/Services/CashRegisterService.php`
- Create: `app/Services/ReportService.php`
- Test: `tests/Feature/OrderAndCashServicesTest.php`

- [ ] **Step 1: Write failing feature test covering order creation, stock deduction, and cash register close difference**
- [ ] **Step 2: Implement Services logic**
- [ ] **Step 3: Run test to verify**
Run: `php artisan test --filter=OrderAndCashServicesTest`
Expected: PASS
- [ ] **Step 4: Commit**

```bash
git add app/Services/ tests/Feature/OrderAndCashServicesTest.php
git commit -m "feat(services): implement order, inventory, cash, and reporting services"
```

---

### Task 6: REST API Controllers, Form Requests, Resources & Routes

**Files:**
- Create: `app/Http/Requests/*` (Auth, Product, Order, Invoice, CashRegister)
- Create: `app/Http/Resources/*` (Uniform JSON wrappers)
- Create: `app/Http/Controllers/Api/*` (All endpoints)
- Modify: `routes/api.php`
- Test: `tests/Feature/ApiEndpointsTest.php`

- [ ] **Step 1: Write failing tests for login, product CRUD, order transitions, checkout and reports API**
- [ ] **Step 2: Implement Controllers delegating to domain Services and returning standard JSON `{ status, message, data }`**
- [ ] **Step 3: Run tests**
Run: `php artisan test --filter=ApiEndpointsTest`
Expected: PASS
- [ ] **Step 4: Commit**

```bash
git add app/Http/ routes/api.php tests/Feature/ApiEndpointsTest.php
git commit -m "feat(api): complete api endpoints, validation requests and json resources"
```

---

### Task 7: Database Seeders (Real Ice Cream Catalog, Tables, Roles & Users)

**Files:**
- Create: `database/seeders/RolesAndUsersSeeder.php`
- Create: `database/seeders/IceCreamCatalogSeeder.php`
- Create: `database/seeders/TablesSeeder.php`
- Create: `database/seeders/BusinessSettingsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Define realistic data:**
  - 4 Users: `admin@heladeria.com`, `cajero@heladeria.com`, `mesero@heladeria.com`, `cocina@heladeria.com` (password: `password`).
  - Categories: Helados Tradicionales, Helados Gourmet, Copas Especiales, Bebidas, Toppings.
  - Products with variants: Copas 1/2/3 bolas, litros, conos, batidos, salsas.
  - 8 Tables: Mesa 1 to Mesa 8.
- [ ] **Step 2: Run seeder**
Run: `php artisan db:seed --force`
Expected: Database seeded successfully.
- [ ] **Step 3: Commit**

```bash
git add database/seeders/
git commit -m "feat(seeders): populate realistic ice cream parlour demo dataset"
```

---

### Task 8: Frontend Setup (Vue 3, Tailwind CSS, Pinia, Vue Router & Layouts)

**Files:**
- Create: `package.json`, `vite.config.js`, `tailwind.config.js`
- Create: `resources/views/app.blade.php`
- Create: `resources/js/app.js`
- Create: `resources/js/router/index.js`
- Create: `resources/js/components/Navbar.vue`, `resources/js/components/Sidebar.vue`
- Create: `resources/js/stores/auth.js`

- [ ] **Step 1: Install frontend dependencies (`vue`, `vue-router`, `pinia`, `tailwindcss`, `@vitejs/plugin-vue`, `lucide-vue-next`, `chart.js`, `vue-chartjs`)**
- [ ] **Step 2: Setup Tailwind and App layout with responsive Sidebar filtering menu items based on user roles**
- [ ] **Step 3: Verify build compiles cleanly**
Run: `npm run build`
Expected: Assets generated without error.
- [ ] **Step 4: Commit**

```bash
git add package.json vite.config.js tailwind.config.js resources/
git commit -m "feat(frontend): setup vue 3 spa architecture, tailwind and base layout"
```

---

### Task 9: Frontend Views Implementation (Login, POS/Tables, Order Drawer, Checkout)

**Files:**
- Create: `resources/js/views/LoginView.vue`
- Create: `resources/js/views/PosView.vue`
- Create: `resources/js/components/OrderDrawer.vue`
- Create: `resources/js/views/CheckoutView.vue`
- Create: `resources/js/stores/tables.js`
- Create: `resources/js/stores/orders.js`

- [ ] **Step 1: Implement Login with Sanctum token storage and user profile hydration**
- [ ] **Step 2: Implement POS with table status cards (Free, Occupied, Reserved) and takeaway order button**
- [ ] **Step 3: Implement OrderDrawer: category filter tabs, flavor/variant selector, notes, live subtotal**
- [ ] **Step 4: Implement Checkout: split bill calculator, payment method selector (cash, card, mixed), print receipt button triggering DomPDF download**
- [ ] **Step 5: Verify build**
Run: `npm run build`
Expected: PASS
- [ ] **Step 6: Commit**

```bash
git add resources/js/
git commit -m "feat(frontend): implement pos, tables grid, order taking, and checkout view"
```

---

### Task 10: Frontend Views Implementation (Products/Stock, Cash Register, Finance Reports, Settings)

**Files:**
- Create: `resources/js/views/ProductsView.vue`
- Create: `resources/js/views/CashRegisterView.vue`
- Create: `resources/js/views/ReportsView.vue`
- Create: `resources/js/views/SettingsView.vue`
- Create: `resources/js/stores/cash.js`

- [ ] **Step 1: Implement ProductsView with search, category filtering, low-stock warning tags, and variant modal**
- [ ] **Step 2: Implement CashRegisterView with open balance modal, cash in/out movement recorder, and close balance discrepancy display**
- [ ] **Step 3: Implement ReportsView with Chart.js charts (sales per day, top flavors, income vs expenses) and summary KPI cards**
- [ ] **Step 4: Implement SettingsView for business information and DIAN/Internal billing mode toggle**
- [ ] **Step 5: Build and compile frontend**
Run: `npm run build`
Expected: Assets compiled cleanly.
- [ ] **Step 6: Commit**

```bash
git add resources/js/
git commit -m "feat(frontend): implement inventory, cash register, analytics reports, and settings"
```

---

### Task 11: End-to-End Verification & Documentation

**Files:**
- Create: `README.md` (Detailed installation, migrations, default credentials, billing mode usage)
- Test: Full backend test suite execution

- [ ] **Step 1: Run complete test suite**
Run: `php artisan test`
Expected: All tests PASS with zero failures.
- [ ] **Step 2: Build production frontend assets**
Run: `npm run build`
Expected: Built cleanly.
- [ ] **Step 3: Write comprehensive `README.md` with setup guide, API examples and credentials**
- [ ] **Step 4: Commit**

```bash
git add README.md
git commit -m "docs: add complete project installation and usage guide"
```
