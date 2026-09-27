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
        $category = Category::create(['name' => 'Helados', 'slug' => 'helados-' . uniqid()]);

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
