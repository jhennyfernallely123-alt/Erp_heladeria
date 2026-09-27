# Módulo de Productos independiente del Stock — Plan de Implementación

> **For agentic workers:** este plan lo ejecuta **un solo agente, sin subagentes**. Cada tarea se completa y se commitea antes de pasar a la siguiente.

**Goal:** Separar el stock del producto en la base de datos y reconstruir la interfaz con un sistema de diseño basado en iconos, de modo que el módulo Productos no exponga ningún dato de inventario.

**Architecture:** El stock se muda a dos tablas nuevas (`product_stocks`, `stock_movements`). `InventoryService` mantiene su firma pública exacta y por dentro opera sobre `product_stocks`, así que `OrderService`, los dos provedores de factura y `ReportService` no se modifican. En el frontend, un sistema de primitivos reutilizables bajo `components/ui/` sostiene un `AppShell` con TopBar y Sidebar data-driven, y dos vistas: Productos (sin stock) e Inventario (todo el stock).

**Tech Stack:** Laravel 11, MySQL, Eloquent, Sanctum, PHPUnit 10 · Vue 3 `<script setup>`, Pinia, Vue Router 4, Tailwind CSS 3.4, `lucide-vue-next` 0.359, Vite 5.

**Spec:** `docs/superpowers/specs/2026-09-27-modulo-productos-independiente-stock-design.md`

---

## Mapa de archivos

### Backend — se crean
| Archivo | Responsabilidad |
|---|---|
| `database/migrations/2026_09_27_100000_create_product_stocks_table.php` | Existencias por producto y por variante |
| `database/migrations/2026_09_27_100001_create_stock_movements_table.php` | Historial de entradas, salidas y ajustes |
| `database/migrations/2026_09_27_100002_separate_stock_from_products.php` | Copia el stock, elimina las columnas, agrega `image` y `deleted_at`, corrige la FK de `order_items` |
| `app/Models/ProductStock.php` | Existencias, con `status()` |
| `app/Models/StockMovement.php` | Auditoría de movimientos |
| `app/Http/Controllers/Api/InventoryController.php` | Listado, stock bajo y ajuste |
| `tests/Feature/ProductInventorySeparationTest.php` | Contrato de la separación |

### Backend — se modifican
| Archivo | Cambio |
|---|---|
| `app/Models/Product.php` | `SoftDeletes`, sin columnas de stock, relación `stock()` |
| `app/Models/ProductVariant.php` | Sin `stock_quantity`, relación `stock()` |
| `app/Services/InventoryService.php` | Misma firma, implementación sobre `product_stocks` |
| `app/Http/Controllers/Api/ProductController.php` | Sin stock, subida de imagen, `toggle-active`, borrado lógico |
| `routes/api.php` | Rutas de inventario, quita `adjust-stock` |
| `database/seeders/IceCreamCatalogSeeder.php` | Crea filas de stock |
| `tests/Feature/ModelRelationshipTest.php` | Se adapts al esquema nuevo (única prueba tocada) |

### Frontend — se crean
| Archivo | Responsabilidad |
|---|---|
| `resources/js/config/navigation.js` | Las 6 entradas del menú con icono y roles |
| `resources/js/config/categoryIcons.js` | Nombre de categoría → componente lucide |
| `resources/js/components/ui/AppIcon.vue` | Acceso único a lucide |
| `resources/js/components/ui/AppButton.vue` | Botón con variantes e icono |
| `resources/js/components/ui/AppBadge.vue` | Badge de tono |
| `resources/js/components/ui/AppInput.vue` | Input con label y error |
| `resources/js/components/ui/AppSelect.vue` | Select con label y error |
| `resources/js/components/ui/AppTextarea.vue` | Textarea con label y error |
| `resources/js/components/ui/AppModal.vue` | Diálogo modal |
| `resources/js/components/ui/ConfirmDialog.vue` | Confirmación destructiva |
| `resources/js/components/ui/AppTable.vue` | Tabla con carga, vacío y slots |
| `resources/js/components/ui/AppEmptyState.vue` | Estado vacío |
| `resources/js/components/ui/AppPagination.vue` | Pie con "Mostrando X-Y de Z" |
| `resources/js/components/ui/StatCard.vue` | Métrica con icono |
| `resources/js/components/ui/PageHeader.vue` | Título, subtítulo, acciones |
| `resources/js/components/ui/AppToast.vue` | Contenedor de notificaciones |
| `resources/js/stores/toast.js` | Cola de notificaciones |
| `resources/js/stores/products.js` | Estado y acciones de Productos |
| `resources/js/stores/inventory.js` | Estado y acciones de Inventario |
| `resources/js/components/AppShell.vue` | Layout sidebar + columna principal |
| `resources/js/components/TopBar.vue` | Barra superior con chip de usuario |
| `resources/js/components/ProductFormModal.vue` | Formulario de alta y edición |
| `resources/js/components/StockAdjustModal.vue` | Ajuste de stock |
| `resources/js/views/InventoryView.vue` | Pantalla de Inventario |

### Frontend — se modifican
| Archivo | Cambio |
|---|---|
| `resources/js/components/Sidebar.vue` | Data-driven desde `navigation.js`, iconos lucide |
| `resources/js/App.vue` | Usa `AppShell` y monta `AppToast` |
| `resources/js/views/ProductsView.vue` | Reescrita por completo, sin stock |
| `resources/js/router/index.js` | Ruta `/inventario` |

**Nota de verificación frontend:** el proyecto no tiene framework de pruebas (`package.json` no define script `test`, no hay vitest ni @vue/test-utils). La verificación del frontend es `npm run build` más revisión manual. No se introduce vitest en este alcance.

---

## Fase A — Backend

### Task 1: Tablas de stock

**Files:**
- Create: `database/migrations/2026_09_27_100000_create_product_stocks_table.php`
- Create: `database/migrations/2026_09_27_100001_create_stock_movements_table.php`

- [ ] **Step 1: Crear la migración de `product_stocks`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->decimal('min_alert', 12, 2)->default(5.00);
            $table->enum('stock_type', ['unit', 'bulk_grams'])->default('unit');
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id'], 'product_stocks_product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
```

- [ ] **Step 2: Crear la migración de `stock_movements`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_stock_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
```

- [ ] **Step 3: Verificar que las migraciones aplican**

Run: `php artisan migrate --force`
Expected: `2026_09_27_100000_create_product_stocks_table ... DONE` y `2026_09_27_100001_create_stock_movements_table ... DONE`

- [ ] **Step 4: Commit**

```bash
git add database/migrations
git commit -m "feat(db): add product_stocks and stock_movements tables"
```

---

### Task 2: Migración de separación de datos

**Files:**
- Create: `database/migrations/2026_09_27_100002_separate_stock_from_products.php`

Esta migración copia el stock existente antes de eliminar las columnas. El orden importa: primero crea `product_stocks` y `stock_movements` (Task 1), luego copia, y solo entonces elimina.

- [ ] **Step 1: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Copia el stock del producto base a product_stocks.
        DB::table('products')->orderBy('id')->chunk(200, function ($products) {
            foreach ($products as $product) {
                DB::table('product_stocks')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'quantity' => $product->stock_quantity,
                    'min_alert' => $product->min_stock_alert,
                    'stock_type' => $product->stock_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // 2. Copia el stock de cada variante a su propia fila.
        DB::table('product_variants')->orderBy('id')->chunk(200, function ($variants) {
            foreach ($variants as $variant) {
                DB::table('product_stocks')->insert([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $variant->stock_quantity,
                    'min_alert' => 5.00,
                    'stock_type' => 'unit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // 3. products se queda solo con la identidad y el valor del producto.
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'min_stock_alert', 'stock_type']);
            $table->string('image')->nullable()->after('description');
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('stock_quantity');
        });

        // 4. Borrar un producto ya no puede destruir el historial de ventas.
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image', 'deleted_at']);
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
            $table->decimal('min_stock_alert', 12, 2)->default(5.00);
            $table->enum('stock_type', ['unit', 'bulk_grams'])->default('unit');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
        });

        DB::table('product_stocks')->whereNull('product_variant_id')->orderBy('id')->each(function ($stock) {
            DB::table('products')->where('id', $stock->product_id)->update([
                'stock_quantity' => $stock->quantity,
                'min_stock_alert' => $stock->min_alert,
                'stock_type' => $stock->stock_type,
            ]);
        });

        DB::table('product_stocks')
            ->whereNotNull('product_variant_id')
            ->orderBy('id')
            ->get()
            ->each(function ($stock) {
                DB::table('product_variants')->where('id', $stock->product_variant_id)->update([
                    'stock_quantity' => $stock->quantity,
                ]);
            });

        // Deshace la copia de up(). Las tablas product_stocks y stock_movements
        // pertenecen a las migraciones 100000 y 100001 y no se eliminan aquí:
        // hacerlas drop aquí dejaba el esquema sin poder volver a migrar.
        // Se vacían porque en este punto son un espejo de products/product_variants,
        // y conservarlas rompería la unicidad cuando up() se ejecute de nuevo
        // (el índice único no cubre product_variant_id = NULL en MySQL).
        DB::table('stock_movements')->delete();
        DB::table('product_stocks')->delete();
    }
};
```

- [ ] **Step 2: Aplicar y verificar que los datos se copiaron**

Run: `php artisan migrate --force`
Expected: `2026_09_27_100002_separate_stock_from_products ... DONE`

Run: `php artisan tinker --execute="echo App\Models\Product::count().' productos, '.App\Models\ProductStock::count().' filas de stock (antes de crear los modelos: use DB; echo DB::table('products')->count().' productos, '.DB::table('product_stocks')->count().' filas de stock');"`
Expected: una línea con el conteo de productos y un número igual o mayor de filas de stock (una por producto más una por variante).

Run: `php artisan tinker --execute="use DB; print_r(array_keys(DB::table('products')->first() ? (array) DB::table('products')->first() : []));"`
Expected: un arreglo con `id, category_id, name, description, image, cost_price, sale_price, has_variants, is_active, created_at, updated_at, deleted_at` y **sin** `stock_quantity`, `min_stock_alert` ni `stock_type`.

- [ ] **Step 3: Verificar que el rollback funciona**

La migración se aplicó sobre una base con datos reales (9 productos, 8 variantes, 2 pedidos), así que la copia es verificable, no teórica.

Run: `php artisan migrate:rollback --step=1 --force`
Expected: la migración se revierte sin error y `products` vuelve a tener `stock_quantity` con los valores originales.

Run: `php artisan migrate --force`
Expected: vuelve a aplicarse y `product_stocks` queda con 17 filas (9 productos + 8 variantes).

**Aviso:** `migrate:rollback --step=1` revierte solo la última migración aplicada, que puede no ser la 100002 si ya se revirtió antes. Para un ciclo limpio usar `migrate:rollback --step=3`, que revierte 100002, 100001 y 100000, y después `migrate --force`. Ese `--step=3` también revierte `192008_create_business_settings_table`, que se recupera con el `migrate` siguiente.

- [ ] **Step 4: Commit**

```bash
git add database/migrations
git commit -m "feat(db): move stock out of products and protect sales history"
```

---

### Task 3: Modelos ProductStock y StockMovement

**Files:**
- Create: `app/Models/ProductStock.php`
- Create: `app/Models/StockMovement.php`

- [ ] **Step 1: Crear `ProductStock`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'min_alert',
        'stock_type',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_alert' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function status(): string
    {
        if ((float) $this->quantity <= 0) {
            return 'critical';
        }

        if ((float) $this->quantity <= (float) $this->min_alert) {
            return 'low';
        }

        return 'normal';
    }

    public function formattedQuantity(): string
    {
        if ($this->stock_type === 'bulk_grams') {
            return rtrim(rtrim(number_format((float) $this->quantity, 3), '0'), '.') . ' kg';
        }

        return number_format((float) $this->quantity, 0);
    }

    public static function resolve(Product $product, ?ProductVariant $variant = null): self
    {
        return self::firstOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
            ],
            [
                'quantity' => 0,
                'min_alert' => 5,
                'stock_type' => 'unit',
            ]
        );
    }
}
```

- [ ] **Step 2: Crear `StockMovement`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_stock_id',
        'type',
        'quantity',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Verificar sintaxis**

Run: `php -l app/Models/ProductStock.php; php -l app/Models/StockMovement.php`
Expected: `No syntax errors detected` en ambos.

- [ ] **Step 4: Commit**

```bash
git add app/Models/ProductStock.php app/Models/StockMovement.php
git commit -m "feat(models): add ProductStock and StockMovement"
```

---

### Task 4: Actualizar Product y ProductVariant

**Files:**
- Modify: `app/Models/Product.php`
- Modify: `app/Models/ProductVariant.php`

- [ ] **Step 1: Reescribir `Product.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'cost_price',
        'sale_price',
        'has_variants',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stock()
    {
        return $this->hasOne(ProductStock::class)->whereNull('product_variant_id');
    }

    public function isLowStock(): bool
    {
        if (!$this->stock) {
            return false;
        }

        return $this->stock->status() !== 'normal';
    }
}
```

- [ ] **Step 2: Reescribir `ProductVariant.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'cost_price',
        'sale_price',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stock()
    {
        return $this->hasOne(ProductStock::class, 'product_variant_id');
    }
}
```

**Las dos relaciones `stock()` no son intercambiables y no pueden ser un `hasOne` simple.** La fila de stock del producto base y las de sus variantes comparten `product_id`, así que un `hasOne(ProductStock::class)` sin más devuelve una fila arbitraria: la del producto base acabaría leyendo el stock de la primera variante. Por eso `Product` añade `whereNull('product_variant_id')` y `ProductVariant` declara la clave foránea explícita (`'product_variant_id'`, con `id` como local key). `InventoryServiceTest::test_product_and_variant_stock_rows_do_not_collide` existe para que esta regresión no vuelva.

- [ ] **Step 3: Verificar sintaxis y que la app sigue cargando**

Run: `php -l app/Models/Product.php; php -l app/Models/ProductVariant.php; php artisan about --only=environment`
Expected: `No syntax errors detected` en ambos y el comando `about` responde sin excepción.

- [ ] **Step 4: Commit**

```bash
git add app/Models/Product.php app/Models/ProductVariant.php
git commit -m "feat(models): strip stock from Product and ProductVariant"
```

---

### Task 5: InventoryService sobre product_stocks

**Files:**
- Modify: `app/Services/InventoryService.php`

La firma pública no cambia. Eso es lo que garantiza que `OrderService`, `ReportService` y los provedores de factura sigan funcionando sin tocar una línea.

- [ ] **Step 1: Escribir la prueba que falla primero**

Crear `tests/Feature/InventoryServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'Helados', 'slug' => 'helados']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Vaso de Helado',
            'cost_price' => 2000,
            'sale_price' => 6000,
        ]);
    }

    public function test_deduct_stock_creates_the_stock_row_when_missing(): void
    {
        $product = $this->makeProduct();

        $this->service->deductStock($product, null, 3);

        $this->assertEquals(1, ProductStock::count());
        $this->assertEquals('-3.00', ProductStock::first()->quantity);
    }

    public function test_deduct_and_restore_move_the_same_row(): void
    {
        $product = $this->makeProduct();
        $this->service->adjustStock($product, null, 20, 'Carga inicial');

        $this->service->deductStock($product, null, 5);
        $this->assertEquals('15.00', $product->stock()->first()->quantity);

        $this->service->restoreStock($product, null, 5);
        $this->assertEquals('20.00', $product->stock()->first()->quantity);
        $this->assertEquals(1, ProductStock::count());
    }

    public function test_adjust_stock_targets_the_variant_row_when_given_a_variant(): void
    {
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 Bolas',
            'cost_price' => 3000,
            'sale_price' => 9000,
        ]);

        $this->service->adjustStock($product, $variant, 12, 'Carga inicial');

        $this->assertEquals('12.00', $variant->stock()->first()->quantity);

        // Un producto que solo tiene variantes no recibe fila de stock base:
        // el stock vive donde se gestiona, no se duplica en el producto.
        $this->assertNull($product->stock()->first());
        $this->assertEquals(1, ProductStock::count());
    }

    public function test_product_and_variant_stock_rows_do_not_collide(): void
    {
        $product = $this->makeProduct();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '2 Bolas',
            'cost_price' => 2500,
            'sale_price' => 8500,
        ]);

        $this->service->adjustStock($product, null, 40, 'Carga base');
        $this->service->adjustStock($product, $variant, 12, 'Carga variante');

        $this->assertEquals('40.00', $product->stock()->first()->quantity);
        $this->assertEquals('12.00', $variant->stock()->first()->quantity);
        $this->assertEquals(2, ProductStock::count());
    }

    public function test_get_low_stock_products_only_returns_rows_below_the_alert(): void
    {
        $product = $this->makeProduct();
        $this->service->adjustStock($product, null, 2, 'Carga inicial');

        $low = $this->service->getLowStockProducts();

        $this->assertCount(1, $low);
        $this->assertEquals($product->id, $low->first()->product_id);
    }

    public function test_get_low_stock_products_ignores_normal_rows(): void
    {
        $product = $this->makeProduct();
        $this->service->adjustStock($product, null, 50, 'Carga inicial');

        $this->assertCount(0, $this->service->getLowStockProducts());
    }
}
```

- [ ] **Step 2: Ejecutar la prueba y ver que falla**

Run: `php artisan test --filter=InventoryServiceTest`
Expected: FAIL. La razón es que `adjustStock` y `deductStock` aún escriben en `products.stock_quantity`, que ya no existe, y `getLowStockProducts` compara columnas eliminadas. El error esperado es `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'stock_quantity'`.

- [ ] **Step 3: Reescribir `InventoryService`**

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class InventoryService
{
    public function deductStock(Product $product, ?ProductVariant $variant, float $quantity): void
    {
        $stock = ProductStock::resolve($product, $variant);
        $stock->decrement('quantity', $quantity);

        $stock->movements()->create([
            'type' => 'out',
            'quantity' => $quantity,
            'reason' => 'Venta',
        ]);
    }

    public function restoreStock(Product $product, ?ProductVariant $variant, float $quantity): void
    {
        $stock = ProductStock::resolve($product, $variant);
        $stock->increment('quantity', $quantity);

        $stock->movements()->create([
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'Restitución',
        ]);
    }

    public function adjustStock(Product $product, ?ProductVariant $variant, float $newQuantity, string $reason = '', ?int $userId = null): void
    {
        $stock = ProductStock::resolve($product, $variant);
        $delta = round($newQuantity - (float) $stock->quantity, 2);

        $stock->update(['quantity' => $newQuantity]);

        if ($delta !== 0.0) {
            $stock->movements()->create([
                'type' => 'adjustment',
                'quantity' => $delta,
                'reason' => $reason !== '' ? $reason : 'Ajuste manual',
                'user_id' => $userId,
            ]);
        }
    }

    public function getLowStockProducts(): Collection
    {
        return ProductStock::query()
            ->with(['product.category', 'variant'])
            ->whereColumn('quantity', '<=', 'min_alert')
            ->get();
    }
}
```

- [ ] **Step 4: Ejecutar la prueba y ver que pasa**

Run: `php artisan test --filter=InventoryServiceTest`
Expected: PASS, 5 pruebas, 0 fallos.

- [ ] **Step 5: Commit**

```bash
git add app/Services/InventoryService.php tests/Feature/InventoryServiceTest.php
git commit -m "feat(inventory): move InventoryService onto product_stocks"
```

---

### Task 6: InventoryController y rutas

**Files:**
- Create: `app/Http/Controllers/Api/InventoryController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Escribir la prueba que falla primero**

Crear `tests/Feature/InventoryApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Gerente',
            'email' => 'gerente@heladeria.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('admin');

        return $user->createToken('t')->plainTextToken;
    }

    private function makeStock(float $quantity, float $minAlert = 5): ProductStock
    {
        $category = Category::create([
            'name' => 'Helados ' . uniqid(),
            'slug' => 'helados-' . uniqid(),
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vaso ' . uniqid(),
            'cost_price' => 2000,
            'sale_price' => 6000,
        ]);

        return ProductStock::create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => $quantity,
            'min_alert' => $minAlert,
            'stock_type' => 'unit',
        ]);
    }

    public function test_index_returns_stock_rows_with_stats(): void
    {
        $token = $this->adminToken();
        $this->makeStock(30);
        $this->makeStock(2);
        $this->makeStock(0);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/inventory');

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'quantity', 'min_alert', 'status', 'product' => ['id', 'name', 'category']],
                ],
                'meta' => ['stats' => ['total_products', 'total_units', 'low_count', 'critical_count']],
            ]);

        $stats = $res->json('meta.stats');
        $this->assertEquals(3, $stats['total_products']);
        $this->assertEquals(32, $stats['total_units']);
        $this->assertEquals(2, $stats['low_count']);
        $this->assertEquals(1, $stats['critical_count']);
    }

    public function test_index_can_filter_by_status(): void
    {
        $token = $this->adminToken();
        $this->makeStock(30);
        $this->makeStock(0);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/inventory?status=critical');

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_adjust_updates_quantity_and_writes_a_movement(): void
    {
        $token = $this->adminToken();
        $stock = $this->makeStock(4);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/inventory/{$stock->id}/adjust", [
                'new_quantity' => 25,
                'type' => 'in',
                'reason' => 'Recepción de mercadería',
            ]);

        $res->assertStatus(200);
        $this->assertEquals('25.00', $stock->fresh()->quantity);

        $movement = $stock->movements()->latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertEquals('in', $movement->type);
        $this->assertEquals('21.00', $movement->quantity);
        $this->assertNotNull($movement->user_id);
    }

    public function test_adjust_requires_a_reason(): void
    {
        $token = $this->adminToken();
        $stock = $this->makeStock(4);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/inventory/{$stock->id}/adjust", [
                'new_quantity' => 25,
                'type' => 'in',
            ]);

        $res->assertStatus(422)->assertJsonValidationErrors('reason');
    }

    public function test_cashier_cannot_reach_the_inventory_api(): void
    {
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Cajero',
            'email' => 'cajero@heladeria.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('cashier');
        $token = $user->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/inventory')
            ->assertStatus(403);
    }
}
```

- [ ] **Step 2: Ejecutar y ver que falla**

Run: `php artisan test --filter=InventoryApiTest`
Expected: FAIL con 404, porque `/api/v1/inventory` no existe.

- [ ] **Step 3: Crear `InventoryController`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends BaseApiController
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = ProductStock::with(['product.category', 'variant']);

        if ($request->has('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        match ($request->query('status')) {
            'critical' => $query->whereColumn('quantity', '<=', 0),
            'low' => $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', DB::raw('min_alert')),
            default => null,
        };

        $stocks = $query->orderByRaw('CASE WHEN quantity <= 0 THEN 0 WHEN quantity <= min_alert THEN 1 ELSE 2 END')
            ->orderBy('id')
            ->get();

        $stats = [
            'total_products' => $stocks->count(),
            'total_units' => (float) $stocks->sum(fn ($s) => (float) $s->quantity),
            'low_count' => $stocks->filter(fn ($s) => $s->status() === 'low')->count(),
            'critical_count' => $stocks->filter(fn ($s) => $s->status() === 'critical')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inventario consultado exitosamente',
            'data' => $stocks,
            'meta' => ['stats' => $stats],
        ]);
    }

    public function lowStock(): JsonResponse
    {
        return $this->successResponse($this->inventoryService->getLowStockProducts());
    }

    public function adjust(Request $request, ProductStock $stock): JsonResponse
    {
        $validated = $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'type' => 'required|in:in,out,adjustment',
            'reason' => 'required|string|max:255',
        ]);

        $this->inventoryService->adjustStock(
            $stock->product,
            $stock->variant,
            (float) $validated['new_quantity'],
            $validated['reason'],
            $request->user()?->id,
        );

        return $this->successResponse(
            $stock->fresh(['product.category', 'variant']),
            'Stock ajustado exitosamente'
        );
    }
}
```

- [ ] **Step 4: Registrar las rutas**

En `routes/api.php`, reemplazar la línea 26 (`Route::post('/products/{product}/adjust-stock', ...)`) y añadir el bloque de inventario. El bloque completo queda así:

```php
        // Categories & Products
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive']);
        Route::post('/products/{product}/image', [ProductController::class, 'uploadImage']);

        // Inventory (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);
            Route::post('/inventory/{stock}/adjust', [InventoryController::class, 'adjust']);
        });
```

Y añadir el `use` junto a los demás:

```php
use App\Http\Controllers\Api\InventoryController;
```

- [ ] **Step 5: Ejecutar y ver que pasa**

Run: `php artisan test --filter=InventoryApiTest`
Expected: PASS, 5 pruebas, 0 fallos.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/InventoryController.php routes/api.php tests/Feature/InventoryApiTest.php
git commit -m "feat(api): add inventory endpoints with stats and movement audit"
```

---

### Task 7: ProductController sin stock, con imagen y borrado lógico

**Files:**
- Modify: `app/Http/Controllers/Api/ProductController.php`

- [ ] **Step 1: Escribir la prueba que falla primero**

Crear `tests/Feature/ProductApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Gerente',
            'email' => 'gerente@heladeria.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('admin');
        $this->token = $user->createToken('t')->plainTextToken;

        $this->categoryId = Category::create([
            'name' => 'Helados',
            'slug' => 'helados',
            'icon' => 'ice-cream',
        ])->id;
    }

    private function post(string $uri, array $data = [])
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")->postJson($uri, $data);
    }

    public function test_product_json_never_exposes_stock(): void
    {
        $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Vaso de Helado',
            'cost_price' => 2000,
            'sale_price' => 6000,
        ])->assertStatus(201);

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/products');

        $res->assertStatus(200);

        $body = $res->json('data.0');
        $this->assertArrayNotHasKey('stock_quantity', $body);
        $this->assertArrayNotHasKey('min_stock_alert', $body);
        $this->assertArrayNotHasKey('stock_type', $body);
    }

    public function test_stock_fields_sent_by_a_client_are_ignored(): void
    {
        $res = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Cono Sencillo',
            'cost_price' => 1500,
            'sale_price' => 5000,
            'stock_quantity' => 99,
            'min_stock_alert' => 42,
            'stock_type' => 'bulk_grams',
        ]);

        $res->assertStatus(201);
        $this->assertEquals(1, ProductStock::count());
        $this->assertEquals('0.00', ProductStock::first()->quantity);
    }

    public function test_upload_image_stores_the_file_and_saves_the_path(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Sundae',
            'cost_price' => 3000,
            'sale_price' => 10000,
        ])->json('data.id');

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->image('sundae.jpg', 400, 400),
            ], ['Accept' => 'application/json']);

        $res->assertStatus(200);
        $path = $res->json('data.image');
        $this->assertNotNull($path);
        $this->assertStringStartsWith('products/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_image_rejects_a_file_over_2mb(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Paleta',
            'cost_price' => 1000,
            'sale_price' => 4000,
        ])->json('data.id');

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('enorme.jpg', 3000, 'image/jpeg'),
            ], ['Accept' => 'application/json']);

        $res->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Malheada',
            'cost_price' => 3500,
            'sale_price' => 9000,
        ])->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/products/{$productId}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) Product::find($productId)->is_active);
    }

    public function test_delete_is_soft_and_keeps_the_row(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Copa Brownie',
            'cost_price' => 5000,
            'sale_price' => 12000,
        ])->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/products/{$productId}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $productId]);

        $list = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/products');

        $this->assertCount(0, $list->json('data'));
    }

    public function test_order_deducts_and_cancellation_restores_the_stock(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Vaso de Helado',
            'cost_price' => 2000,
            'sale_price' => 6000,
        ])->json('data.id');

        ProductStock::resolve(Product::find($productId))->update([
            'quantity' => 30,
            'min_alert' => 5,
        ]);

        $orderRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/orders', [
                'table_id' => null,
                'items' => [
                    ['product_id' => $productId, 'quantity' => 4],
                ],
            ]);

        $orderRes->assertStatus(201);
        $this->assertEquals('26.00', ProductStock::first()->fresh()->quantity);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/orders/{$orderRes->json('data.id')}/status", ['status' => 'cancelled'])
            ->assertStatus(200);

        $this->assertEquals('30.00', ProductStock::first()->fresh()->quantity);
    }

    public function test_deleting_a_sold_product_keeps_the_order_and_its_invoice(): void
    {
        $productId = $this->post('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Sundae Clásico',
            'cost_price' => 3000,
            'sale_price' => 10000,
        ])->json('data.id');

        $orderRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/orders', [
                'table_id' => null,
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ]);

        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.id');

        $invoiceRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/invoices', [
                'order_id' => $orderId,
                'customer_name' => 'Cliente de Prueba',
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 10000],
                ],
                'billing_mode' => 'internal',
            ]);

        $invoiceRes->assertStatus(201);
        $invoiceId = $invoiceRes->json('data.invoice.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/products/{$productId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => $orderId]);
        $this->assertDatabaseHas('order_items', ['order_id' => $orderId]);
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoiceId]);
        $this->assertNull(
            DB::table('order_items')->where('order_id', $orderId)->value('product_id'),
            'El producto queda desvinculado del pedido, pero el pedido sobrevive.'
        );
    }
}
```

Añadir el import que usan las dos pruebas nuevas:

```php
use Illuminate\Support\Facades\DB;
```

- [ ] **Step 2: Ejecutar y ver que falla**

Run: `php artisan test --filter=ProductApiTest`
Expected: FAIL. Las rutas `/toggle-active` y `/image` devuelven 404, el alta exige `stock_type`, `stock_quantity` y `min_stock_alert` (422), y `destroy` borra de verdad.

- [ ] **Step 3: Reescribir `ProductController`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'variants']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();

        return $this->successResponse($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules(required: true));

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($validated);
            $this->syncVariants($product, $validated['variants'] ?? []);
            ProductStock::resolve($product);

            return $product;
        });

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Producto creado exitosamente',
            201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse($product->load(['category', 'variants']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate($this->rules(required: false));

        DB::transaction(function () use ($product, $validated) {
            $product->update($validated);

            if (array_key_exists('variants', $validated)) {
                $product->variants()->delete();
                $this->syncVariants($product, $validated['variants'] ?? []);
            }
        });

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Producto actualizado exitosamente'
        );
    }

    public function toggleActive(Product $product): JsonResponse
    {
        $product->update(['is_active' => !$product->is_active]);

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            $product->is_active ? 'Producto activado' : 'Producto desactivado'
        );
    }

    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image' => $path]);

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Imagen actualizada exitosamente'
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->successResponse(null, 'Producto eliminado exitosamente');
    }

    private function rules(bool $required): array
    {
        $must = $required ? 'required' : 'sometimes|required';

        return [
            'category_id' => "{$must}|exists:categories,id",
            'name' => "{$must}|string|max:255",
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:255',
            'cost_price' => "{$must}|numeric|min:0",
            'sale_price' => "{$must}|numeric|min:0",
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.cost_price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'required|numeric|min:0',
        ];
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            $created = $product->variants()->create([
                'name' => $variant['name'],
                'cost_price' => $variant['cost_price'],
                'sale_price' => $variant['sale_price'],
            ]);

            ProductStock::resolve($product, $created);
        }
    }
}
```

- [ ] **Step 4: Ejecutar y ver que pasa**

Run: `php artisan test --filter=ProductApiTest`
Expected: PASS, 6 pruebas, 0 fallos.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/ProductController.php tests/Feature/ProductApiTest.php
git commit -m "feat(api): strip stock from product endpoints and add image upload"
```

---

### Task 8: Actualizar el seeder

**Files:**
- Modify: `database/seeders/IceCreamCatalogSeeder.php`

El seeder tiene 9 productos y 7 variantes, y hoy escribe `stock_quantity` y `min_stock_alert` dentro de cada `firstOrCreate`. Añadir una línea de `ProductStock::create` junto a cada una de esas 16 llamadas es frágil y repetitivo. Se reemplaza por dos helpers privados que reciben la cantidad y el mínimo, y que crean la fila de stock como efecto secundario.

- [ ] **Step 1: Añadir el import y los dos helpers**

En el bloque `use` de arriba del archivo, añadir:

```php
use App\Models\ProductStock;
```

Y antes del cierre de la clase, añadir los dos helpers:

```php
    private function makeProduct(
        string $name,
        Category $category,
        array $attributes,
        int $quantity,
        int $minAlert = 5,
        string $stockType = 'unit'
    ): Product {
        $product = Product::firstOrCreate(['name' => $name], array_merge([
            'category_id' => $category->id,
        ], $attributes));

        ProductStock::resolve($product)->update([
            'quantity' => $quantity,
            'min_alert' => $minAlert,
            'stock_type' => $stockType,
        ]);

        return $product;
    }

    private function makeVariant(Product $product, string $name, int $costPrice, int $salePrice, int $quantity): ProductVariant
    {
        $variant = ProductVariant::firstOrCreate(
            ['product_id' => $product->id, 'name' => $name],
            ['cost_price' => $costPrice, 'sale_price' => $salePrice]
        );

        ProductStock::resolve($product, $variant)->update([
            'quantity' => $quantity,
            'min_alert' => 5,
            'stock_type' => 'unit',
        ]);

        return $variant;
    }
```

- [ ] **Step 2: Reemplazar las 9 creaciones de producto**

Sustituir cada bloque `Product::firstOrCreate(...)` por una llamada a `makeProduct` que pase la cantidad y el mínimo fuera del array de atributos. La tabla completa de reemplazos:

| Antes | Después |
|---|---|
| `$prodCono = Product::firstOrCreate(['name' => 'Cono Artesanal'], [... 'stock_quantity' => 100, 'min_stock_alert' => 20, 'has_variants' => true])` | `$prodCono = $this->makeProduct('Cono Artesanal', $catHelados, ['description' => 'Cono de galleta crocante con helado de la casa', 'cost_price' => 1500, 'sale_price' => 5000, 'has_variants' => true], 100, 20)` |
| `$prodVaso = Product::firstOrCreate(['name' => 'Vaso Helado'], [... 'stock_quantity' => 80, 'min_stock_alert' => 15, 'has_variants' => true])` | `$prodVaso = $this->makeProduct('Vaso Helado', $catHelados, ['description' => 'Vaso biodegradable con bolas de helado a elección', 'cost_price' => 1400, 'sale_price' => 4500, 'has_variants' => true], 80, 15)` |
| `Product::firstOrCreate(['name' => 'Copa Brownie Explosion'], [... 'stock_quantity' => 25, 'min_stock_alert' => 5, 'has_variants' => false])` | `$this->makeProduct('Copa Brownie Explosion', $catCopas, ['description' => 'Brownie caliente, 2 bolas de helado de vainilla, salsa fudge y crema chantilly', 'cost_price' => 4500, 'sale_price' => 14000, 'has_variants' => false], 25)` |
| `Product::firstOrCreate(['name' => 'Banana Split Clásica'], [... 'stock_quantity' => 20, 'min_stock_alert' => 5, 'has_variants' => false])` | `$this->makeProduct('Banana Split Clásica', $catCopas, ['description' => 'Banano fresco con tres bolas de helado (fresa, vainilla, chocolate), cerezas y barquillo', 'cost_price' => 5000, 'sale_price' => 16000, 'has_variants' => false], 20)` |
| `$prodPote = Product::firstOrCreate(['name' => 'Pote Familiar'], [... 'stock_quantity' => 40, 'min_stock_alert' => 10, 'has_variants' => true])` | `$prodPote = $this->makeProduct('Pote Familiar', $catLitros, ['description' => 'Pote térmico para llevar a casa con sabores a elección', 'cost_price' => 8000, 'sale_price' => 22000, 'has_variants' => true], 40, 10, 'bulk_grams')` |
| `Product::firstOrCreate(['name' => 'Malteada Especial'], [... 'stock_quantity' => 50, 'min_stock_alert' => 10, 'has_variants' => false])` | `$this->makeProduct('Malteada Especial', $catBebidas, ['description' => 'Batido cremoso de helado con leche entera, decorado con salsa y crema', 'cost_price' => 3500, 'sale_price' => 11000, 'has_variants' => false], 50, 10)` |
| `Product::firstOrCreate(['name' => 'Café Affogato'], [... 'stock_quantity' => 60, 'min_stock_alert' => 10, 'has_variants' => false])` | `$this->makeProduct('Café Affogato', $catBebidas, ['description' => 'Shot de espresso caliente sobre una bola de helado de vainilla artesanal', 'cost_price' => 2000, 'sale_price' => 7000, 'has_variants' => false], 60, 10)` |
| `Product::firstOrCreate(['name' => 'Lluvia de Chocolate / Maní'], [... 'stock_quantity' => 150, 'min_stock_alert' => 30, 'has_variants' => false])` | `$this->makeProduct('Lluvia de Chocolate / Maní', $catToppings, ['description' => 'Porción extra de chispas o maní crocante', 'cost_price' => 500, 'sale_price' => 1500, 'has_variants' => false], 150, 30)` |
| `Product::firstOrCreate(['name' => 'Salsa Caliente de Arequipe'], [... 'stock_quantity' => 4, 'min_stock_alert' => 10, 'has_variants' => false])` | `$this->makeProduct('Salsa Caliente de Arequipe', $catToppings, ['description' => 'Porción de salsa caliente de arequipe tradicional', 'cost_price' => 800, 'sale_price' => 2000, 'has_variants' => false], 4, 10)` |

`Pote Familiar` usa `bulk_grams` a propósito, para que la columna Cantidad de Inventario ejercite el formato en kilos.

- [ ] **Step 3: Reemplazar las 7 creaciones de variante**

| Antes | Después |
|---|---|
| `ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '1 Bola'], [... 'stock_quantity' => 100])` | `$this->makeVariant($prodCono, '1 Bola', 1500, 5000, 100)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '2 Bolas'], [... 'stock_quantity' => 100])` | `$this->makeVariant($prodCono, '2 Bolas', 2500, 8500, 100)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '3 Bolas'], [... 'stock_quantity' => 100])` | `$this->makeVariant($prodCono, '3 Bolas', 3500, 11500, 100)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Pequeño (1 bola)'], [... 'stock_quantity' => 80])` | `$this->makeVariant($prodVaso, 'Pequeño (1 bola)', 1400, 4500, 80)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Mediano (2 bolas)'], [... 'stock_quantity' => 80])` | `$this->makeVariant($prodVaso, 'Mediano (2 bolas)', 2400, 8000, 80)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Grande (3 bolas)'], [... 'stock_quantity' => 80])` | `$this->makeVariant($prodVaso, 'Grande (3 bolas)', 3400, 11000, 80)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodPote->id, 'name' => 'Medio Litro'], [... 'stock_quantity' => 40])` | `$this->makeVariant($prodPote, 'Medio Litro', 8000, 22000, 40)` |
| `ProductVariant::firstOrCreate(['product_id' => $prodPote->id, 'name' => 'Un Litro'], [... 'stock_quantity' => 40])` | `$this->makeVariant($prodPote, 'Un Litro', 14000, 38000, 40)` |

Son 8 variantes, no 7: el archivo original tiene 3 de Cono, 3 de Vaso y 2 de Pote. El total esperado es 9 productos + 8 variantes = **17 filas en `product_stocks`**.

- [ ] **Step 4: Ejecutar el seeder y verificar el conteo**

Run: `php artisan migrate:fresh --seed --force`
Expected: termina sin error.

Run: `php artisan tinker --execute="use DB; echo DB::table('products')->count().' productos / '.DB::table('product_variants')->count().' variantes / '.DB::table('product_stocks')->count().' filas de stock';"`
Expected: `9 productos / 8 variantes / 17 filas de stock`

- [ ] **Step 5: Commit**

```bash
git add database/seeders/IceCreamCatalogSeeder.php
git commit -m "feat(seed): create product_stocks rows for the demo catalog"
```

---

### Task 9: Adaptar ModelRelationshipTest al esquema nuevo

Única prueba existente que se modifica. `tests/Feature/ModelRelationshipTest.php:44-65` crea el producto con `stock_quantity` y `min_stock_alert` y afirma `assertFalse($product->isLowStock())`.

**Files:**
- Modify: `tests/Feature/ModelRelationshipTest.php:44-65`

- [ ] **Step 1: Cambiar la creación del producto y de la variante**

```php
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Copa Brownie',
            'cost_price' => 5000.00,
            'sale_price' => 12000.00,
            'has_variants' => true,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 15.00,
            'min_alert' => 5.00,
            'stock_type' => 'unit',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Tamaño Grande 3 Bolas',
            'cost_price' => 7000.00,
            'sale_price' => 16000.00,
        ]);
```

- [ ] **Step 2: Añadir el import**

```php
use App\Models\ProductStock;
```

- [ ] **Step 3: Ejecutar la suite completa**

Run: `php artisan test`
Expected: PASS completo, sin fallos ni errores. `ApiEndpointsTest` sigue verde porque los campos de stock que envía se descartan en silencio y `deductStock` crea la fila ausente con `firstOrCreate`.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/ModelRelationshipTest.php
git commit -m "test: adapt model relationships to the separated stock schema"
```

---

## Fase B — Fundaciones del frontend

### Task 10: Configuración de navegación e iconos

**Files:**
- Create: `resources/js/config/navigation.js`
- Create: `resources/js/config/categoryIcons.js`

- [ ] **Step 1: Crear `navigation.js`**

```javascript
import {
    LayoutDashboard,
    IceCreamBowl,
    Boxes,
    Wallet,
    BarChart3,
    Settings,
} from 'lucide-vue-next';

export const navItems = [
    { label: 'Inicio', route: '/', name: 'pos', icon: LayoutDashboard, roles: ['admin', 'cashier', 'waiter', 'kitchen'] },
    { label: 'Productos', route: '/productos', name: 'products', icon: IceCreamBowl, roles: ['admin', 'cashier'] },
    { label: 'Inventario', route: '/inventario', name: 'inventory', icon: Boxes, roles: ['admin'] },
    { label: 'Caja', route: '/caja', name: 'cash-register', icon: Wallet, roles: ['admin', 'cashier'] },
    { label: 'Reportes', route: '/reportes', name: 'reports', icon: BarChart3, roles: ['admin'] },
    { label: 'Configuración', route: '/configuracion', name: 'settings', icon: Settings, roles: ['admin'] },
];

export const visibleNavItems = (roles = []) =>
    navItems.filter((item) => item.roles.some((role) => roles.includes(role)));
```

- [ ] **Step 2: Crear `categoryIcons.js`**

```javascript
import {
    IceCreamBowl,
    GlassWater,
    Package,
    Coffee,
    Sparkles,
    CakeSlice,
} from 'lucide-vue-next';

const categoryIconMap = {
    'ice-cream': IceCreamBowl,
    glass: GlassWater,
    box: Package,
    coffee: Coffee,
    sparkles: Sparkles,
    cake: CakeSlice,
};

export const resolveCategoryIcon = (icon) => categoryIconMap[icon] || IceCreamBowl;
```

- [ ] **Step 3: Verificar que los iconos importados existen en la librería instalada**

Run: `node -e "const l=require('lucide-vue-next');['LayoutDashboard','IceCreamBowl','Boxes','Wallet','BarChart3','Settings','GlassWater','Package','Coffee','Sparkles','CakeSlice'].forEach(n=>{ if(!l[n]) { console.error('FALTA '+n); process.exit(1);} }); console.log('todos los iconos existen');"`
Expected: `todos los iconos existen`

- [ ] **Step 4: Commit**

```bash
git add resources/js/config
git commit -m "feat(ui): add navigation and category icon configuration"
```

---

### Task 11: Primitivos base de interfaz

**Files:**
- Create: `resources/js/components/ui/AppIcon.vue`
- Create: `resources/js/components/ui/AppButton.vue`
- Create: `resources/js/components/ui/AppBadge.vue`
- Create: `resources/js/components/ui/AppEmptyState.vue`

- [ ] **Step 1: `AppIcon.vue`**

```vue
<template>
    <component :is="name" :size="size" :stroke-width="strokeWidth" aria-hidden="true" />
</template>

<script setup>
defineProps({
    name: { type: [Object, Function], required: true },
    size: { type: [Number, String], default: 16 },
    strokeWidth: { type: [Number, String], default: 2 },
});
</script>
```

- [ ] **Step 2: `AppButton.vue`**

```vue
<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]"
        :class="[sizes[size], variants[variant]]"
    >
        <span v-if="loading" class="inline-flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ loadingText || label }}</span>
        </span>
        <template v-else>
            <AppIcon v-if="icon" :name="icon" :size="iconSize" />
            <span v-if="label">{{ label }}</span>
        </template>
    </button>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    iconSize: { type: Number, default: 16 },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    loadingText: { type: String, default: '' },
});

const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
};

const variants = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm',
    secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
    ghost: 'text-slate-600 hover:bg-slate-100',
    danger: 'bg-rose-600 text-white hover:bg-rose-700',
};
</script>
```

- [ ] **Step 3: `AppBadge.vue`**

```vue
<template>
    <span
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
        :class="tones[tone]"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dots[tone]" />
        <slot>{{ label }}</slot>
    </span>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    tone: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
});

const tones = {
    neutral: 'bg-slate-100 text-slate-700',
    success: 'bg-emerald-50 text-emerald-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-rose-50 text-rose-700',
    info: 'bg-indigo-50 text-indigo-700',
};

const dots = {
    neutral: 'bg-slate-400',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
    info: 'bg-indigo-500',
};
</script>
```

- [ ] **Step 4: `AppEmptyState.vue`**

```vue
<template>
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <AppIcon :name="icon" :size="26" class="text-slate-400" />
        </div>
        <p class="text-sm font-semibold text-slate-800">{{ title }}</p>
        <p class="text-xs text-slate-500 mt-1 max-w-sm">{{ description }}</p>
    </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: { type: String, default: 'Sin resultados' },
    description: { type: String, default: 'Prueba ajustando los filtros de búsqueda.' },
    icon: { type: [Object, Function], default: () => Inbox },
});
</script>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/ui
git commit -m "feat(ui): add icon, button, badge and empty state primitives"
```

---

### Task 12: Primitivos de formulario

**Files:**
- Create: `resources/js/components/ui/AppInput.vue`
- Create: `resources/js/components/ui/AppSelect.vue`
- Create: `resources/js/components/ui/AppTextarea.vue`

- [ ] **Step 1: `AppInput.vue`**

```vue
<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="inputId" class="block text-xs font-semibold text-slate-700">
            {{ label }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
        </label>
        <div class="relative">
            <AppIcon
                v-if="icon"
                :name="icon"
                :size="16"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />
            <input
                :id="inputId"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-slate-50"
                :class="[
                    icon ? 'pl-9' : '',
                    error ? 'border-rose-300 bg-rose-50/40' : 'border-slate-300 bg-white',
                ]"
            />
        </div>
        <p v-if="error" class="text-xs text-rose-600 flex items-center gap-1">
            <AppIcon :name="AlertCircle" :size="13" />{{ error }}
        </p>
        <p v-else-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed, useId } from 'vue';
import AppIcon from './AppIcon.vue';
import { AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: [String, Number], default: '' });
const inputId = useId();
</script>
```

- [ ] **Step 2: `AppSelect.vue`**

```vue
<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="selectId" class="block text-xs font-semibold text-slate-700">
            {{ label }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
        </label>
        <select
            :id="selectId"
            v-model="model"
            :disabled="disabled"
            class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-slate-50"
            :class="error ? 'border-rose-300 bg-rose-50/40' : 'border-slate-300 bg-white'"
        >
            <slot />
        </select>
        <p v-if="error" class="text-xs text-rose-600 flex items-center gap-1">
            <AppIcon :name="AlertCircle" :size="13" />{{ error }}
        </p>
    </div>
</template>

<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: [String, Number], default: null });
const selectId = useId();
</script>
```

- [ ] **Step 3: `AppTextarea.vue`**

```vue
<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="textareaId" class="block text-xs font-semibold text-slate-700">
            {{ label }}
        </label>
        <textarea
            :id="textareaId"
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y"
            :class="error ? 'border-rose-300 bg-rose-50/40' : 'border-slate-300 bg-white'"
        />
        <p v-if="error" class="text-xs text-rose-600 flex items-center gap-1">
            <AppIcon :name="AlertCircle" :size="13" />{{ error }}
        </p>
    </div>
</template>

<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    rows: { type: Number, default: 3 },
    error: { type: String, default: '' },
});

const model = defineModel({ type: String, default: '' });
const textareaId = useId();
</script>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/ui
git commit -m "feat(ui): add input, select and textarea primitives"
```

---

### Task 13: Modal y confirmación

**Files:**
- Create: `resources/js/components/ui/AppModal.vue`
- Create: `resources/js/components/ui/ConfirmDialog.vue`

- [ ] **Step 1: `AppModal.vue`**

```vue
<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-6"
                @click.self="emit('close')"
                @keydown.esc="emit('close')"
            >
                <div
                    class="w-full bg-white rounded-2xl shadow-2xl my-8"
                    :class="sizes[size]"
                >
                    <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ title }}</h2>
                            <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">{{ subtitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                            aria-label="Cerrar"
                            @click="emit('close')"
                        >
                            <AppIcon :name="X" :size="18" />
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue';
import AppIcon from './AppIcon.vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

const lockScroll = () => {
    document.body.style.overflow = 'hidden';
};

const unlockScroll = () => {
    document.body.style.overflow = '';
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            lockScroll();
        } else {
            unlockScroll();
        }
    }
);

onUnmounted(unlockScroll);
</script>
```

- [ ] **Step 2: `ConfirmDialog.vue`**

```vue
<template>
    <AppModal :open="open" :title="title" size="sm" @close="emit('cancel')">
        <p class="text-sm text-slate-600">{{ message }}</p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" :label="cancelText" @click="emit('cancel')" />
                <AppButton :variant="danger ? 'danger' : 'primary'" :label="confirmText" :loading="loading" @click="emit('confirm')" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar acción' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Confirmar' },
    cancelText: { type: String, default: 'Cancelar' },
    danger: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/ui
git commit -m "feat(ui): add modal and confirmation dialog"
```

---

### Task 14: Tabla, paginación, métrica, encabezado y notificaciones

**Files:**
- Create: `resources/js/components/ui/AppTable.vue`
- Create: `resources/js/components/ui/AppPagination.vue`
- Create: `resources/js/components/ui/StatCard.vue`
- Create: `resources/js/components/ui/PageHeader.vue`
- Create: `resources/js/components/ui/AppToast.vue`
- Create: `resources/js/stores/toast.js`

- [ ] **Step 1: Crear `stores/toast.js`**

```javascript
import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
    state: () => ({
        items: [],
    }),

    actions: {
        push(type, message, timeout = 4000) {
            const id = nextId++;
            this.items.push({ id, type, message });

            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout);
            }

            return id;
        },

        success(message) {
            return this.push('success', message);
        },

        error(message) {
            return this.push('error', message, 6000);
        },

        info(message) {
            return this.push('info', message);
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    },
});
```

- [ ] **Step 2: `AppTable.vue`**

`AppTable` se encarga de la carga, el estado vacío y el marco de la tabla. **No** hace paginación: un componente no puede recortar el contenido que le pasa un slot, así que la paginación vive en la vista, que sí conoce sus datos. Cada vista usa `AppTable` para el marco y `AppPagination` debajo, sobre su propio `paged` computado.

```vue
<template>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-4 space-y-3">
            <div v-for="row in skeletonRows" :key="row" class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-lg bg-slate-100 animate-pulse" />
                <div class="h-3 rounded bg-slate-100 animate-pulse flex-1" />
                <div class="h-3 rounded bg-slate-100 animate-pulse w-24" />
                <div class="h-3 rounded bg-slate-100 animate-pulse w-16" />
            </div>
        </div>

        <div v-else-if="total === 0" class="px-6 py-4">
            <slot name="empty">
                <AppEmptyState :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" />
            </slot>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                        <slot name="head" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <slot />
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import AppEmptyState from './AppEmptyState.vue';
import { PackageOpen } from 'lucide-vue-next';

defineProps({
    loading: { type: Boolean, default: false },
    total: { type: Number, default: 0 },
    emptyTitle: { type: String, default: 'Sin resultados' },
    emptyDescription: { type: String, default: 'Prueba ajustando los filtros de búsqueda.' },
    emptyIcon: { type: [Object, Function], default: () => PackageOpen },
});

const skeletonRows = 6;
</script>
```

- [ ] **Step 3: `AppPagination.vue`**

```vue
<template>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/60">
        <p class="text-xs text-slate-500">
            Mostrando <span class="font-semibold text-slate-700">{{ from }}</span>-
            <span class="font-semibold text-slate-700">{{ to }}</span> de
            <span class="font-semibold text-slate-700">{{ total }}</span> registros
        </p>

        <div v-if="pageCount > 1" class="flex items-center gap-1">
            <button
                type="button"
                class="p-1.5 rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="page === 1"
                aria-label="Página anterior"
                @click="go(page - 1)"
            >
                <AppIcon :name="ChevronLeft" :size="16" />
            </button>

            <button
                v-for="(n, index) in pages"
                :key="index"
                type="button"
                class="min-w-8 h-8 px-2 rounded-lg text-xs font-semibold transition"
                :class="n === page ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-white'"
                :disabled="n === '...'"
                @click="typeof n === 'number' && go(n)"
            >
                {{ n }}
            </button>

            <button
                type="button"
                class="p-1.5 rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="page === pageCount"
                aria-label="Página siguiente"
                @click="go(page + 1)"
            >
                <AppIcon :name="ChevronRight" :size="16" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    total: { type: Number, required: true },
    perPage: { type: Number, default: 10 },
});

const page = defineModel('page', { type: Number, default: 1 });

const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from = computed(() => (props.total === 0 ? 0 : (page.value - 1) * props.perPage + 1));
const to = computed(() => Math.min(page.value * props.perPage, props.total));

const pages = computed(() => {
    const count = pageCount.value;

    if (count <= 7) {
        return Array.from({ length: count }, (_, i) => i + 1);
    }

    if (page.value <= 3) {
        return [1, 2, 3, 4, '...', count];
    }

    if (page.value >= count - 2) {
        return [1, '...', count - 3, count - 2, count - 1, count];
    }

    return [1, '...', page.value - 1, page.value, page.value + 1, '...', count];
});

const go = (next) => {
    if (next < 1 || next > pageCount.value || next === page.value) {
        return;
    }

    page.value = next;
};
</script>
```

- [ ] **Step 4: `StatCard.vue`**

```vue
<template>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
        <div class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0" :class="tones[tone]">
            <AppIcon :name="icon" :size="20" />
        </div>
        <div class="min-w-0">
            <p class="text-xs text-slate-500 font-medium truncate">{{ label }}</p>
            <p class="text-xl font-bold text-slate-900 leading-tight">{{ value }}</p>
        </div>
    </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Object, Function], required: true },
    tone: { type: String, default: 'indigo' },
});

const tones = {
    indigo: 'bg-indigo-50 text-indigo-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
};
</script>
```

- [ ] **Step 5: `PageHeader.vue`**

```vue
<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-slate-500 mt-1">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>
```

- [ ] **Step 6: `AppToast.vue`**

```vue
<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[60] w-full max-w-sm space-y-2">
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-4"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 translate-x-4"
            >
                <div
                    v-for="item in toastStore.items"
                    :key="item.id"
                    class="flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg"
                    :class="borders[item.type]"
                >
                    <AppIcon :name="icons[item.type]" :size="18" class="shrink-0 mt-0.5" :class="texts[item.type]" />
                    <p class="text-sm text-slate-700 flex-1">{{ item.message }}</p>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600"
                        aria-label="Cerrar"
                        @click="toastStore.dismiss(item.id)"
                    >
                        <AppIcon :name="X" :size="16" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import AppIcon from './AppIcon.vue';
import { useToastStore } from '../../stores/toast';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';

const toastStore = useToastStore();

const icons = { success: CheckCircle2, error: AlertCircle, info: Info };
const texts = { success: 'text-emerald-600', error: 'text-rose-600', info: 'text-indigo-600' };
const borders = {
    success: 'border-emerald-200',
    error: 'border-rose-200',
    info: 'border-indigo-200',
};
</script>
```

- [ ] **Step 7: Verificar que los iconos existen**

Run: `node -e "const l=require('lucide-vue-next');['ChevronLeft','ChevronRight','CheckCircle2','AlertCircle','Info','X','PackageOpen','Inbox','ImagePlus'].forEach(n=>{if(!l[n]){console.error('FALTA '+n);process.exit(1);}});console.log('ok');"`
Expected: `ok`

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/ui resources/js/stores/toast.js
git commit -m "feat(ui): add table, pagination, stat card, page header and toasts"
```

---

## Fase C — Shell

### Task 15: AppShell, TopBar y Sidebar

**Files:**
- Create: `resources/js/components/AppShell.vue`
- Create: `resources/js/components/TopBar.vue`
- Modify: `resources/js/components/Sidebar.vue`
- Modify: `resources/js/App.vue`

- [ ] **Step 1: Reescribir `Sidebar.vue` sin emojis**

```vue
<template>
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col min-h-screen shrink-0 select-none">
        <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-800">
            <div class="h-9 w-9 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
                <AppIcon :name="IceCreamBowl" :size="19" class="text-white" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate leading-tight">Nieve Real</p>
                <p class="text-[11px] text-slate-400">Heladería & POS</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <router-link
                v-for="item in items"
                :key="item.name"
                :to="item.route"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                :class="isActive(item) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
            >
                <span
                    class="w-1 h-5 rounded-r-full shrink-0"
                    :class="isActive(item) ? 'bg-white' : 'bg-transparent'"
                />
                <AppIcon :name="item.icon" :size="17" class="shrink-0" />
                <span>{{ item.label }}</span>
            </router-link>
        </nav>
    </aside>
</template>

<script setup>
import AppIcon from './ui/AppIcon.vue';
import { useAuthStore } from '../stores/auth';
import { visibleNavItems } from '../config/navigation';
import { IceCreamBowl } from 'lucide-vue-next';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const authStore = useAuthStore();
const route = useRoute();

const items = computed(() => visibleNavItems(authStore.roles));
const isActive = (item) => route.name === item.name;
</script>
```

- [ ] **Step 2: Crear `TopBar.vue`**

```vue
<template>
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between gap-4 px-6 shrink-0">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 truncate">{{ currentTitle }}</p>
        </div>

        <div class="relative">
            <button
                type="button"
                class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-xl hover:bg-slate-50 transition"
                @click="menuOpen = !menuOpen"
            >
                <span class="h-8 w-8 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                    {{ initials }}
                </span>
                <span class="hidden sm:block text-left">
                    <span class="block text-xs font-semibold text-slate-800 leading-tight">{{ authStore.user?.name }}</span>
                    <span class="block text-[10px] uppercase tracking-wide text-slate-400 leading-tight">
                        {{ (authStore.user?.roles || [])[0] || 'Usuario' }}
                    </span>
                </span>
                <AppIcon :name="ChevronDown" :size="15" class="text-slate-400" />
            </button>

            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 scale-95"
                leave-active-class="transition duration-75 ease-in"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="menuOpen"
                    class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-50"
                >
                    <div class="px-4 py-2 border-b border-slate-100">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ authStore.user?.name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ authStore.user?.email }}</p>
                    </div>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition"
                        @click="handleLogout"
                    >
                        <AppIcon :name="LogOut" :size="16" class="text-slate-400" />
                        Cerrar sesión
                    </button>
                </div>
            </Transition>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import AppIcon from './ui/AppIcon.vue';
import { useAuthStore } from '../stores/auth';
import { navItems } from '../config/navigation';
import { ChevronDown, LogOut } from 'lucide-vue-next';

const authStore = useAuthStore();
const router = useRouter();
const menuOpen = ref(false);

const initials = computed(() =>
    (authStore.user?.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('')
);

const currentTitle = computed(() => {
    const match = navItems.find((item) => item.name === router.currentRoute.value.name);
    return match ? match.label : '';
});

const handleLogout = async () => {
    menuOpen.value = false;
    await authStore.logout();
    router.push('/login');
};

const onClickOutside = (event) => {
    if (menuOpen.value && !event.target.closest('.relative')) {
        menuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>
```

- [ ] **Step 3: Crear `AppShell.vue`**

```vue
<template>
    <div class="min-h-screen flex bg-slate-100 font-sans">
        <Sidebar />
        <div class="flex-1 flex flex-col min-w-0 h-screen">
            <TopBar />
            <main class="flex-1 overflow-y-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';
</script>
```

- [ ] **Step 4: Actualizar `App.vue`**

```vue
<template>
    <AppShell v-if="authStore.isAuthenticated" />
    <router-view v-else />
    <AppToast />
</template>

<script setup>
import { useAuthStore } from './stores/auth';
import AppShell from './components/AppShell.vue';
import AppToast from './components/ui/AppToast.vue';

const authStore = useAuthStore();
</script>
```

- [ ] **Step 5: Verificar que compila**

Run: `npm run build`
Expected: `built in` sin errores. Si aparece `Failed to resolve component`, falta un import.

- [ ] **Step 6: Commit**

```bash
git add resources/js/App.vue resources/js/components/AppShell.vue resources/js/components/TopBar.vue resources/js/components/Sidebar.vue
git commit -m "feat(ui): add app shell with topbar and icon-driven sidebar"
```

---

## Fase D — Módulo Productos

### Task 16: Store de productos

**Files:**
- Create: `resources/js/stores/products.js`

- [ ] **Step 1: Crear el store**

```javascript
import { defineStore } from 'pinia';
import api from '../api';

export const useProductStore = defineStore('products', {
    state: () => ({
        items: [],
        categories: [],
        loading: false,
        saving: false,
        uploadingImage: false,
        error: null,
    }),

    getters: {
        activeItems: (state) => state.items.filter((item) => item.is_active),
        byId: (state) => (id) => state.items.find((item) => item.id === id),
    },

    actions: {
        async fetchProducts(filters = {}) {
            this.loading = true;
            this.error = null;

            try {
                const params = {};
                if (filters.search) params.search = filters.search;
                if (filters.categoryId) params.category_id = filters.categoryId;

                const res = await api.get('/products', { params });
                this.items = res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cargar el catálogo';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const res = await api.get('/categories');
                this.categories = res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudieron cargar las categorías';
                throw err;
            }
        },

        async createProduct(payload) {
            this.saving = true;

            try {
                const res = await api.post('/products', payload);
                await this.fetchProducts();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },

        async updateProduct(id, payload) {
            this.saving = true;

            try {
                const res = await api.put(`/products/${id}`, payload);
                await this.fetchProducts();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },

        async deleteProduct(id) {
            await api.delete(`/products/${id}`);
            this.items = this.items.filter((item) => item.id !== id);
        },

        async toggleActive(id) {
            const res = await api.patch(`/products/${id}/toggle-active`);
            const updated = res.data.data;
            const index = this.items.findIndex((item) => item.id === id);

            if (index !== -1) {
                this.items[index] = updated;
            }

            return updated;
        },

        async uploadImage(id, file) {
            this.uploadingImage = true;

            try {
                const form = new FormData();
                form.append('image', file);

                const res = await api.post(`/products/${id}/image`, form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                const updated = res.data.data;
                const index = this.items.findIndex((item) => item.id === id);

                if (index !== -1) {
                    this.items[index] = updated;
                }

                return updated;
            } finally {
                this.uploadingImage = false;
            }
        },
    },
});
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/stores/products.js
git commit -m "feat(store): add products pinia store"
```

---

### Task 17: Formulario de producto

**Files:**
- Create: `resources/js/components/ProductFormModal.vue`

- [ ] **Step 1: Crear el componente**

```vue
<template>
    <AppModal
        :open="open"
        :title="isEditing ? 'Editar producto' : 'Nuevo producto'"
        subtitle="Los datos de stock se gestionan en el módulo de Inventario"
        size="lg"
        @close="emit('close')"
    >
        <div class="flex gap-1 p-1 bg-slate-100 rounded-xl mb-5">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition"
                :class="activeTab === tab.id ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </div>

        <div v-if="activeTab === 'data'" class="space-y-4">
            <div>
                <p class="text-xs font-semibold text-slate-700 mb-1.5">Imagen del producto</p>
                <div class="flex items-center gap-4">
                    <div class="h-20 w-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                        <img v-if="imagePreview" :src="imagePreview" alt="Vista previa" class="h-full w-full object-cover" />
                        <AppIcon v-else-if="form.image" :name="Package" :size="26" class="text-slate-400" />
                        <AppIcon v-else :name="ImagePlus" :size="26" class="text-slate-400" />
                    </div>
                    <div class="space-y-2">
                        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFileChange" />
                        <AppButton variant="secondary" size="sm" label="Seleccionar imagen" :icon="Upload" @click="fileInput.click()" />
                        <p class="text-[11px] text-slate-500">JPG, PNG o WEBP. Máximo 2MB.</p>
                    </div>
                </div>
            </div>

            <AppInput v-model="form.name" label="Nombre" placeholder="Vaso de helado" :required="true" :error="errors.name || ''" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <AppSelect v-model="form.category_id" label="Categoría" :required="true" :error="errors.category_id || ''">
                    <option :value="null" disabled>Selecciona una categoría</option>
                    <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </AppSelect>

                <AppInput v-model="form.sale_price" label="Precio de venta" type="number" :required="true" :error="errors.sale_price || ''" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <AppInput v-model="form.cost_price" label="Precio de costo" type="number" :error="errors.cost_price || ''" />
                <div class="space-y-1.5">
                    <p class="text-xs font-semibold text-slate-700">Margen estimado</p>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold" :class="marginClass">
                        {{ marginLabel }}
                    </div>
                </div>
            </div>

            <AppTextarea v-model="form.description" label="Descripción" :rows="3" :error="errors.description || ''" />

            <label class="flex items-center gap-2.5 cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700">Producto activo</span>
            </label>
        </div>

        <div v-else class="space-y-3">
            <div v-for="(variant, index) in form.variants" :key="index" class="rounded-xl border border-slate-200 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-700">Variante {{ index + 1 }}</p>
                    <button type="button" class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="removeVariant(index)">
                        <AppIcon :name="Trash2" :size="15" />
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <AppInput v-model="variant.name" label="Nombre" placeholder="3 Bolas" />
                    <AppInput v-model="variant.cost_price" label="Costo" type="number" />
                    <AppInput v-model="variant.sale_price" label="Venta" type="number" />
                </div>
            </div>

            <AppButton variant="secondary" size="sm" label="Agregar variante" :icon="Plus" @click="addVariant" />
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton :label="isEditing ? 'Guardar cambios' : 'Crear producto'" :loading="productStore.saving" @click="save" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AppModal from './ui/AppModal.vue';
import AppButton from './ui/AppButton.vue';
import AppInput from './ui/AppInput.vue';
import AppSelect from './ui/AppSelect.vue';
import AppTextarea from './ui/AppTextarea.vue';
import AppIcon from './ui/AppIcon.vue';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';
import { ImagePlus, Package, Plus, Trash2, Upload } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    product: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const productStore = useProductStore();
const toastStore = useToastStore();
const fileInput = ref(null);
const activeTab = ref('data');
const errors = ref({});
const pendingFile = ref(null);
const imagePreview = ref('');

const tabs = [
    { id: 'data', label: 'Datos' },
    { id: 'variants', label: 'Variantes' },
];

const isEditing = computed(() => props.product !== null);

const emptyForm = () => ({
    name: '',
    category_id: null,
    description: '',
    cost_price: 0,
    sale_price: 0,
    is_active: true,
    variants: [],
});

const form = reactive(emptyForm());

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        errors.value = {};
        activeTab.value = 'data';
        pendingFile.value = null;
        imagePreview.value = props.product?.image ? `/storage/${props.product.image}` : '';

        Object.assign(form, emptyForm());

        if (props.product) {
            form.name = props.product.name;
            form.category_id = props.product.category_id;
            form.description = props.product.description || '';
            form.cost_price = props.product.cost_price;
            form.sale_price = props.product.sale_price;
            form.is_active = props.product.is_active;
            form.variants = (props.product.variants || []).map((variant) => ({
                name: variant.name,
                cost_price: variant.cost_price,
                sale_price: variant.sale_price,
            }));
        }
    }
);

const margin = computed(() => Number(form.sale_price || 0) - Number(form.cost_price || 0));
const marginClass = computed(() => (margin.value > 0 ? 'text-emerald-700' : 'text-slate-500'));

const marginLabel = computed(() => {
    const sale = Number(form.sale_price || 0);
    const cost = Number(form.cost_price || 0);

    if (sale <= 0) {
        return '—';
    }

    const percent = Math.round((margin.value / sale) * 100);
    return `$ ${margin.value.toLocaleString('es-CO')} (${percent}%)`;
});

const onFileChange = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        errors.value = { ...errors.value, image: 'La imagen no puede superar los 2MB.' };
        event.target.value = '';
        return;
    }

    pendingFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
    delete errors.value.image;
};

const addVariant = () => {
    form.variants.push({ name: '', cost_price: 0, sale_price: 0 });
};

const removeVariant = (index) => {
    form.variants.splice(index, 1);
};

const save = async () => {
    errors.value = {};

    const payload = {
        name: form.name,
        category_id: form.category_id,
        description: form.description || null,
        cost_price: Number(form.cost_price || 0),
        sale_price: Number(form.sale_price || 0),
        is_active: form.is_active,
        has_variants: form.variants.length > 0,
        variants: form.variants
            .filter((variant) => variant.name)
            .map((variant) => ({
                name: variant.name,
                cost_price: Number(variant.cost_price || 0),
                sale_price: Number(variant.sale_price || 0),
            })),
    };

    try {
        if (isEditing.value) {
            await productStore.updateProduct(props.product.id, payload);

            if (pendingFile.value) {
                await productStore.uploadImage(props.product.id, pendingFile.value);
            }

            toastStore.success('Producto actualizado');
        } else {
            const created = await productStore.createProduct(payload);

            if (pendingFile.value) {
                await productStore.uploadImage(created.id, pendingFile.value);
            }

            toastStore.success('Producto creado');
        }

        emit('saved');
        emit('close');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
            activeTab.value = 'data';
            toastStore.error('Revisa los campos marcados');
        } else {
            toastStore.error(err.response?.data?.message || 'No se pudo guardar el producto');
        }
    }
};
</script>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/ProductFormModal.vue
git commit -m "feat(products): add product form modal with variants and image upload"
```

---

### Task 18: Vista de productos y ruta

**Files:**
- Modify: `resources/js/views/ProductsView.vue`
- Modify: `resources/js/router/index.js`

- [ ] **Step 1: Reescribir `ProductsView.vue`**

```vue
<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader title="Productos" subtitle="Catálogo de helado, bebidas y postres">
            <template #actions>
                <AppButton label="Nuevo producto" :icon="Plus" @click="openCreate" />
            </template>
        </PageHeader>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar por nombre o descripción" :icon="Search" />
            </div>
            <AppSelect v-model="categoryId" class="min-w-[180px]">
                <option :value="null">Todas las categorías</option>
                <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </AppSelect>
            <AppSelect v-model="statusFilter" class="min-w-[150px]">
                <option value="all">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </AppSelect>
            <span class="text-xs text-slate-500">{{ filtered.length }} productos</span>
        </div>

        <div class="space-y-4">
            <AppTable
                :loading="productStore.loading"
                :total="filtered.length"
                empty-title="Sin productos"
                empty-description="Ajusta la búsqueda o crea el primer producto del catálogo."
            >
                <template #head>
                    <th class="p-3.5">Producto</th>
                    <th class="p-3.5">Categoría</th>
                    <th class="p-3.5 text-right">Precio</th>
                    <th class="p-3.5 text-center">Estado</th>
                    <th class="p-3.5 text-right">Acciones</th>
                </template>

                <tr v-for="product in paged" :key="product.id" class="hover:bg-slate-50 transition-colors">
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                <img v-if="product.image" :src="imageUrl(product.image)" :alt="product.name" class="h-full w-full object-cover" />
                                <AppIcon v-else :name="categoryIcon(product)" :size="20" class="text-slate-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ product.name }}</p>
                                <p v-if="product.description" class="text-xs text-slate-400 truncate">{{ product.description }}</p>
                                <AppBadge
                                    v-if="product.variants?.length"
                                    class="mt-1"
                                    :label="`${product.variants.length} variantes`"
                                />
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <AppBadge :label="product.category?.name || '—'" />
                    </td>
                    <td class="p-3.5 text-right font-semibold text-slate-800">
                        $ {{ formatMoney(product.sale_price) }}
                    </td>
                    <td class="p-3.5 text-center">
                        <AppBadge :tone="product.is_active ? 'success' : 'warning'" dot :label="product.is_active ? 'Activo' : 'Inactivo'" />
                    </td>
                    <td class="p-3.5">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Editar"
                                @click="openEdit(product)"
                            >
                                <AppIcon :name="Pencil" :size="16" />
                            </button>
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition"
                                title="Eliminar"
                                @click="askDelete(product)"
                            >
                                <AppIcon :name="Trash2" :size="16" />
                            </button>
                        </div>
                    </td>
                </tr>
            </AppTable>

            <div v-if="filtered.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <AppPagination v-model:page="page" :total="filtered.length" :per-page="perPage" />
            </div>
        </div>

        <ProductFormModal :open="formOpen" :product="editing" @close="formOpen = false" />

        <ConfirmDialog
            :open="deleteOpen"
            danger
            title="Eliminar producto"
            :message="`¿Eliminar '${productToDelete?.name}'? El producto dejará de aparecer en el catálogo, pero su historial de ventas se conserva.`"
            confirm-text="Eliminar"
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="deleteOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppTable from '../components/ui/AppTable.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import ProductFormModal from '../components/ProductFormModal.vue';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';
import { resolveCategoryIcon } from '../config/categoryIcons';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';

const productStore = useProductStore();
const toastStore = useToastStore();

const perPage = 10;
const search = ref('');
const categoryId = ref(null);
const statusFilter = ref('all');
const page = ref(1);
const formOpen = ref(false);
const editing = ref(null);
const deleteOpen = ref(false);
const productToDelete = ref(null);
const deleting = ref(false);

const formatMoney = (value) => Number(value || 0).toLocaleString('es-CO');
const imageUrl = (path) => `/storage/${path}`;
const categoryIcon = (product) => resolveCategoryIcon(product.category?.icon);

const load = async () => {
    try {
        await Promise.all([productStore.fetchProducts(), productStore.fetchCategories()]);
    } catch (err) {
        toastStore.error(productStore.error || 'No se pudo cargar el catálogo');
    }
};

onMounted(load);

watch([search, categoryId], () => {
    page.value = 1;

    productStore.fetchProducts({
        search: search.value || undefined,
        categoryId: categoryId.value || undefined,
    }).catch(() => {});
});

watch(statusFilter, () => {
    page.value = 1;
});

const filtered = computed(() => {
    if (statusFilter.value === 'all') {
        return productStore.items;
    }

    const wantActive = statusFilter.value === 'active';
    return productStore.items.filter((product) => product.is_active === wantActive);
});

const paged = computed(() => {
    const start = (page.value - 1) * perPage;
    return filtered.value.slice(start, start + perPage);
});

const openCreate = () => {
    editing.value = null;
    formOpen.value = true;
};

const openEdit = (product) => {
    editing.value = product;
    formOpen.value = true;
};

const askDelete = (product) => {
    productToDelete.value = product;
    deleteOpen.value = true;
};

const confirmDelete = async () => {
    deleting.value = true;

    try {
        await productStore.deleteProduct(productToDelete.value.id);
        toastStore.success('Producto eliminado');
        deleteOpen.value = false;
    } catch (err) {
        toastStore.error(err.response?.data?.message || 'No se pudo eliminar el producto');
    } finally {
        deleting.value = false;
    }
};
</script>
```

- [ ] **Step 2: Añadir la ruta de inventario al router**

En `resources/js/router/index.js`, insertar después del bloque de la ruta `products`:

```javascript
    {
        path: '/inventario',
        name: 'inventory',
        component: () => import('../views/InventoryView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
```

- [ ] **Step 3: Verificar que compila**

Run: `npm run build`
Expected: `built in` sin errores.

- [ ] **Step 4: Commit**

```bash
git add resources/js/views/ProductsView.vue resources/js/router/index.js
git commit -m "feat(products): rebuild products view without stock columns"
```

---

## Fase E — Módulo Inventario

### Task 19: Store de inventario

**Files:**
- Create: `resources/js/stores/inventory.js`

- [ ] **Step 1: Crear el store**

```javascript
import { defineStore } from 'pinia';
import api from '../api';

const emptyStats = {
    total_products: 0,
    total_units: 0,
    low_count: 0,
    critical_count: 0,
};

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        items: [],
        stats: { ...emptyStats },
        loading: false,
        saving: false,
        error: null,
    }),

    actions: {
        async fetchInventory(filters = {}) {
            this.loading = true;
            this.error = null;

            try {
                const params = {};
                if (filters.search) params.search = filters.search;
                if (filters.categoryId) params.category_id = filters.categoryId;
                if (filters.status && filters.status !== 'all') params.status = filters.status;

                const res = await api.get('/inventory', { params });
                this.items = res.data.data;
                this.stats = res.data.meta?.stats || { ...emptyStats };
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cargar el inventario';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchLowStock() {
            const res = await api.get('/inventory/low-stock');
            return res.data.data;
        },

        async adjustStock(id, payload) {
            this.saving = true;

            try {
                const res = await api.post(`/inventory/${id}/adjust`, payload);
                const index = this.items.findIndex((item) => item.id === id);

                if (index !== -1) {
                    this.items[index] = res.data.data;
                }

                await this.fetchInventory();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },
    },
});
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/stores/inventory.js
git commit -m "feat(store): add inventory pinia store"
```

---

### Task 20: Modal de ajuste de stock

**Files:**
- Create: `resources/js/components/StockAdjustModal.vue`

- [ ] **Step 1: Crear el componente**

```vue
<template>
    <AppModal
        :open="open"
        title="Ajustar stock"
        :subtitle="stock ? `${stock.product?.name}${stock.variant ? ` · ${stock.variant.name}` : ''}` : ''"
        size="sm"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <div class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                <div>
                    <p class="text-xs text-slate-500">Cantidad actual</p>
                    <p class="text-lg font-bold text-slate-900">{{ currentQuantity }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500">Stock mínimo</p>
                    <p class="text-lg font-bold text-slate-700">{{ minAlert }}</p>
                </div>
            </div>

            <AppInput v-model="newQuantity" label="Nueva cantidad" type="number" :required="true" :error="errors.new_quantity || ''" />

            <AppSelect v-model="type" label="Tipo de movimiento" :required="true" :error="errors.type || ''">
                <option value="in">Entrada</option>
                <option value="out">Salida</option>
                <option value="adjustment">Ajuste</option>
            </AppSelect>

            <AppInput v-model="reason" label="Motivo" placeholder="Recepción de mercadería" :required="true" :error="errors.reason || ''" />

            <div v-if="delta !== 0" class="rounded-xl px-4 py-3 text-sm" :class="delta > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                El stock {{ delta > 0 ? 'aumentará' : 'disminuirá' }} {{ Math.abs(delta) }} {{ unitLabel }}.
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton label="Guardar ajuste" :loading="inventoryStore.saving" @click="save" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import AppModal from './ui/AppModal.vue';
import AppButton from './ui/AppButton.vue';
import AppInput from './ui/AppInput.vue';
import AppSelect from './ui/AppSelect.vue';
import { useInventoryStore } from '../stores/inventory';
import { useToastStore } from '../stores/toast';

const props = defineProps({
    open: { type: Boolean, default: false },
    stock: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const inventoryStore = useInventoryStore();
const toastStore = useToastStore();

const newQuantity = ref(0);
const type = ref('in');
const reason = ref('');
const errors = ref({});

const currentQuantity = computed(() => (props.stock ? Number(props.stock.quantity) : 0));
const minAlert = computed(() => (props.stock ? Number(props.stock.min_alert) : 0));
const unitLabel = computed(() => (props.stock?.stock_type === 'bulk_grams' ? 'kg' : 'unidades'));
const delta = computed(() => Number(newQuantity.value || 0) - currentQuantity.value);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        errors.value = {};
        reason.value = '';
        type.value = 'in';
        newQuantity.value = currentQuantity.value;
    }
);

const save = async () => {
    errors.value = {};

    try {
        await inventoryStore.adjustStock(props.stock.id, {
            new_quantity: Number(newQuantity.value),
            type: type.value,
            reason: reason.value,
        });

        toastStore.success('Stock ajustado');
        emit('saved');
        emit('close');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            toastStore.error(err.response?.data?.message || 'No se pudo ajustar el stock');
        }
    }
};
</script>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/StockAdjustModal.vue
git commit -m "feat(inventory): add stock adjust modal"
```

---

### Task 21: Vista de inventario

**Files:**
- Create: `resources/js/views/InventoryView.vue`

- [ ] **Step 1: Crear la vista**

```vue
<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader title="Inventario" subtitle="Existencias por producto y variante">
            <template #actions>
                <AppButton label="Registrar entrada" :icon="Plus" @click="openAdjust" />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <StatCard label="Productos en inventario" :value="inventoryStore.stats.total_products" :icon="Boxes" tone="indigo" />
            <StatCard label="Unidades en stock" :value="formatNumber(inventoryStore.stats.total_units)" :icon="Package" tone="emerald" />
            <StatCard label="Stock bajo" :value="inventoryStore.stats.low_count" :icon="AlertTriangle" tone="amber" />
            <StatCard label="Stock crítico" :value="inventoryStore.stats.critical_count" :icon="AlertOctagon" tone="rose" />
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar producto" :icon="Search" />
            </div>
            <AppSelect v-model="categoryId" class="min-w-[180px]">
                <option :value="null">Todas las categorías</option>
                <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </AppSelect>
            <AppSelect v-model="statusFilter" class="min-w-[160px]">
                <option value="all">Todos los estados</option>
                <option value="normal">Normal</option>
                <option value="low">Bajo</option>
                <option value="critical">Crítico</option>
            </AppSelect>
        </div>

        <div class="space-y-4">
            <AppTable
                :loading="inventoryStore.loading"
                :total="inventoryStore.items.length"
                empty-title="Sin existencias"
                empty-description="Todavía no hay registros de stock para los productos del catálogo."
            >
                <template #head>
                    <th class="p-3.5">Producto</th>
                    <th class="p-3.5">Categoría</th>
                    <th class="p-3.5">Variante</th>
                    <th class="p-3.5 text-right">Cantidad</th>
                    <th class="p-3.5 text-right">Stock mínimo</th>
                    <th class="p-3.5 text-center">Estado</th>
                    <th class="p-3.5 text-right">Acciones</th>
                </template>

                <tr
                    v-for="row in paged"
                    :key="row.id"
                    class="transition-colors hover:bg-slate-50"
                    :class="row.status === 'critical' ? 'bg-rose-50/40' : ''"
                >
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                <AppIcon :name="categoryIcon(row)" :size="20" class="text-slate-400" />
                            </div>
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ row.product?.name }}</p>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <AppBadge :label="row.product?.category?.name || '—'" />
                    </td>
                    <td class="p-3.5">
                        <span v-if="row.variant" class="text-sm text-slate-700">{{ row.variant.name }}</span>
                        <AppBadge v-else label="Producto base" />
                    </td>
                    <td class="p-3.5 text-right font-semibold text-slate-800">{{ formatQuantity(row) }}</td>
                    <td class="p-3.5 text-right text-slate-500">{{ formatNumber(row.min_alert) }}</td>
                    <td class="p-3.5 text-center">
                        <AppBadge :tone="statusTone(row.status)" dot :label="statusLabel(row.status)" />
                    </td>
                    <td class="p-3.5">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Ajustar stock"
                                @click="openAdjust(row)"
                            >
                                <AppIcon :name="SlidersHorizontal" :size="16" />
                            </button>
                        </div>
                    </td>
                </tr>
            </AppTable>

            <div v-if="inventoryStore.items.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <AppPagination v-model:page="page" :total="inventoryStore.items.length" :per-page="perPage" />
            </div>
        </div>

        <StockAdjustModal :open="adjustOpen" :stock="selected" @close="adjustOpen = false" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppTable from '../components/ui/AppTable.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import StatCard from '../components/ui/StatCard.vue';
import StockAdjustModal from '../components/StockAdjustModal.vue';
import { useInventoryStore } from '../stores/inventory';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';
import { resolveCategoryIcon } from '../config/categoryIcons';
import { AlertOctagon, AlertTriangle, Boxes, Package, Plus, Search, SlidersHorizontal } from 'lucide-vue-next';

const inventoryStore = useInventoryStore();
const productStore = useProductStore();
const toastStore = useToastStore();

const perPage = 10;
const search = ref('');
const categoryId = ref(null);
const statusFilter = ref('all');
const page = ref(1);
const adjustOpen = ref(false);
const selected = ref(null);

const paged = computed(() => {
    const start = (page.value - 1) * perPage;
    return inventoryStore.items.slice(start, start + perPage);
});

const formatNumber = (value) =>
    Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 2 });

const formatQuantity = (row) =>
    row.stock_type === 'bulk_grams'
        ? `${formatNumber(row.quantity)} kg`
        : formatNumber(row.quantity);

const categoryIcon = (row) => resolveCategoryIcon(row.product?.category?.icon);

const statusTone = (status) =>
    ({ normal: 'success', low: 'warning', critical: 'danger' })[status] || 'neutral';

const statusLabel = (status) =>
    ({ normal: 'Normal', low: 'Bajo', critical: 'Crítico' })[status] || '—';

const load = async () => {
    try {
        await Promise.all([
            inventoryStore.fetchInventory({
                search: search.value || undefined,
                categoryId: categoryId.value || undefined,
                status: statusFilter.value,
            }),
            productStore.categories.length === 0
                ? productStore.fetchCategories()
                : Promise.resolve(),
        ]);
    } catch (err) {
        toastStore.error(inventoryStore.error || 'No se pudo cargar el inventario');
    }
};

onMounted(load);

watch([search, categoryId, statusFilter], () => {
    page.value = 1;
    load();
});

const openAdjust = (row) => {
    selected.value = row;
    adjustOpen.value = true;
};
</script>
```

- [ ] **Step 2: Verificar que compila**

Run: `npm run build`
Expected: `built in` sin errores.

- [ ] **Step 3: Commit**

```bash
git add resources/js/views/InventoryView.vue
git commit -m "feat(inventory): add inventory view with stats and stock adjust"
```

---

## Fase F — Verificación final

### Task 22: Verificación completa

**Files:**
- Verify only

- [ ] **Step 1: Ejecutar la suite de pruebas**

Run: `php artisan test`
Expected: todas las pruebas pasan. El total debe ser mayor que el original: se suman `InventoryServiceTest` (5), `InventoryApiTest` (5) y `ProductApiTest` (6).

- [ ] **Step 2: Verificar el seed completo**

Run: `php artisan migrate:fresh --seed --force`
Expected: termina sin error.

Run: `php artisan tinker --execute="use DB; echo DB::table('products')->count().' productos / '.DB::table('product_stocks')->count().' filas de stock / '.DB::table('product_variants')->count().' variantes';"`
Expected: el número de filas de stock es igual al de productos más el de variantes.

- [ ] **Step 3: Compilar el frontend**

Run: `npm run build`
Expected: `built in` sin errores ni advertencias de resolución de componentes.

- [ ] **Step 4: Confirmar que no quedan emojis en el alcance**

Run: `Select-String -Path resources\js\components\ui\*.vue,resources\js\components\AppShell.vue,resources\js\components\TopBar.vue,resources\js\components\Sidebar.vue,resources\js\components\ProductFormModal.vue,resources\js\components\StockAdjustModal.vue,resources\js\views\ProductsView.vue,resources\js\views\InventoryView.vue,resources\js\config\*.js,resources\js\stores\*.js -Pattern '[\uD83C-\uDBFF\uDC00-\uDFFF]'`
Expected: sin resultados. Si aparece alguno, es un emoji que hay que reemplazar por un icono de lucide.

- [ ] **Step 5: Confirmar que Productos no menciona stock**

Run: `Select-String -Path resources\js\views\ProductsView.vue -Pattern 'stock|inventario|existencia' -CaseSensitive:$false`
Expected: la única coincidencia posible es el subtítulo del formulario que dice que el stock se gestiona en Inventario, dentro de `ProductFormModal.vue`. `ProductsView.vue` no debe contener ninguna.

- [ ] **Step 6: Revisar el contrato de la API a mano**

Run: `php artisan serve` en una terminal y, en otra, iniciar sesión y llamar a `GET /api/v1/products`. Verificar que el JSON no trae `stock_quantity`, `min_stock_alert` ni `stock_type`, y que `GET /api/v1/inventory` sí trae `quantity` y `meta.stats`.

- [ ] **Step 7: Commit final si hubo ajustes**

```bash
git add -A
git commit -m "chore: final verification pass for products and inventory modules"
```

---

## Orden de ejecución y puntos de riesgo

El orden importa por una razón concreta: la Task 2 elimina columnas de `products`, y a partir de ahí cualquier código que las escriba falla. Por eso las Tasks 1 a 9 (backend) van completas y commiteadas antes de tocar el frontend.

| Riesgo | Dónde se mitiga |
|---|---|
| `deductStock` sobre una fila de stock inexistente | `ProductStock::resolve()` con `firstOrCreate` en Task 3, verificado por `InventoryServiceTest` |
| Cambiar la FK de `order_items` en MySQL | Se hace en dos llamadas `Schema::table` separadas en Task 2, primero quitar la FK y luego redefinir la columna |
| Los campos de stock que envía el cliente se ignoran en silencio | Documentado en el spec y verificado en `ProductApiTest::test_stock_fields_sent_by_a_client_are_ignored` |
| Una prueba existente codifica el modelo viejo | Task 9 actualiza `ModelRelationshipTest` de forma explícita |
| ProductFormModal tiene varias pestañas | El `watch` sobre `open` reinicia el formulario completo cada vez que se abre |
| El watcher de `ProductsView` dispara peticiones en cada tecla | Se acepta para esta tanda; el buscador es de lado servidor vía `search` |
