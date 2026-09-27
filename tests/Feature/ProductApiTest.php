<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    private function createViaApi(string $uri, array $data = [])
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")->postJson($uri, $data);
    }

    private function createProduct(string $name, int $cost = 2000, int $sale = 6000)
    {
        return $this->createViaApi('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => $name,
            'cost_price' => $cost,
            'sale_price' => $sale,
        ]);
    }

    public function test_product_json_never_exposes_stock(): void
    {
        $this->createProduct('Vaso de Helado')->assertStatus(201);

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
        $res = $this->createViaApi('/api/v1/products', [
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
        $this->assertEquals('unit', ProductStock::first()->stock_type);
    }

    public function test_variants_create_their_own_stock_rows(): void
    {
        $res = $this->createViaApi('/api/v1/products', [
            'category_id' => $this->categoryId,
            'name' => 'Cono Artesanal',
            'cost_price' => 1500,
            'sale_price' => 5000,
            'has_variants' => true,
            'variants' => [
                ['name' => '1 Bola', 'cost_price' => 1500, 'sale_price' => 5000],
                ['name' => '2 Bolas', 'cost_price' => 2500, 'sale_price' => 8500],
            ],
        ]);

        $res->assertStatus(201);
        $this->assertEquals(3, ProductStock::count());
        $this->assertCount(2, $res->json('data.variants'));

        foreach (ProductStock::whereNotNull('product_variant_id')->get() as $stock) {
            $this->assertEquals('0.00', $stock->quantity);
        }
    }

    public function test_upload_image_stores_the_file_and_saves_the_path(): void
    {
        $productId = $this->createProduct('Sundae', 3000, 10000)->json('data.id');

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('sundae.jpg', 120, 'image/jpeg'),
            ], ['Accept' => 'application/json']);

        $res->assertStatus(200);
        $path = $res->json('data.image');
        $this->assertNotNull($path);
        $this->assertStringStartsWith('products/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertEquals($path, Product::find($productId)->image);
    }

    public function test_upload_image_rejects_a_non_image(): void
    {
        $productId = $this->createProduct('Paleta', 1000, 4000)->json('data.id');

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
            ], ['Accept' => 'application/json']);

        $res->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_upload_image_rejects_a_file_over_2mb(): void
    {
        $productId = $this->createProduct('Paleta', 1000, 4000)->json('data.id');

        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('enorme.jpg', 3000, 'image/jpeg'),
            ], ['Accept' => 'application/json']);

        $res->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_upload_image_deletes_the_previous_file(): void
    {
        $productId = $this->createProduct('Sundae', 3000, 10000)->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('primera.jpg', 120, 'image/jpeg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200);

        $first = Product::find($productId)->image;
        Storage::disk('public')->assertExists($first);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->post("/api/v1/products/{$productId}/image", [
                'image' => UploadedFile::fake()->create('segunda.jpg', 120, 'image/jpeg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200);

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists(Product::find($productId)->image);
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $productId = $this->createProduct('Malheada', 3500, 9000)->json('data.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/products/{$productId}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) Product::find($productId)->is_active);
    }

    public function test_delete_is_soft_and_keeps_the_row(): void
    {
        $productId = $this->createProduct('Copa Brownie', 5000, 12000)->json('data.id');

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
        $productId = $this->createProduct('Vaso de Helado')->json('data.id');

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
        $productId = $this->createProduct('Sundae Clásico', 3000, 10000)->json('data.id');

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

        // El borrado lógico es un UPDATE, así que la fila sigue existiendo y el
        // pedido mantiene su product_id. El historial queda intacto.
        $this->assertEquals(
            $productId,
            DB::table('order_items')->where('order_id', $orderId)->value('product_id')
        );
    }

    public function test_force_deleting_a_sold_product_detaches_the_item_without_cascading(): void
    {
        $productId = $this->createProduct('Sundae Clásico', 3000, 10000)->json('data.id');

        $orderRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/orders', [
                'table_id' => null,
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
            ]);

        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.id');

        // Esta es la razón del ON DELETE SET NULL: un borrado real del producto
        // no debe arrastrar por cascada el pedido ni su factura.
        Product::withTrashed()->findOrFail($productId)->forceDelete();

        $this->assertDatabaseHas('orders', ['id' => $orderId]);
        $this->assertNull(
            DB::table('order_items')->where('order_id', $orderId)->value('product_id')
        );
    }
}
