<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_auth_and_product_crud_endpoints(): void
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Gerente Admin',
            'email' => 'gerente@heladeria.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('admin');

        // 1. Login endpoint
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'gerente@heladeria.com',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $token = $loginRes->json('data.token');

        // 2. Create Category
        $catRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/categories', [
                'name' => 'Paletas Gourmet',
                'icon' => 'popsicle',
            ]);

        $catRes->assertStatus(201)
            ->assertJsonPath('data.slug', 'paletas-gourmet');

        $catId = $catRes->json('data.id');

        // 3. Create Product with Variants
        $prodRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/products', [
                'category_id' => $catId,
                'name' => 'Paleta Rellena de Leche Condensada',
                'cost_price' => 3000,
                'sale_price' => 8000,
                'stock_type' => 'unit',
                'stock_quantity' => 25,
                'min_stock_alert' => 5,
            ]);

        $prodRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Paleta Rellena de Leche Condensada');

        // 4. List Products
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $listRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
        $this->assertCount(1, $listRes->json('data'));
    }

    public function test_order_and_invoice_checkout_api_flow(): void
    {
        $user = User::create([
            'name' => 'Cajero 1',
            'email' => 'cajero@heladeria.com',
            'password' => bcrypt('password123'),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $cat = Category::create(['name' => 'Helados', 'slug' => 'helados']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Sundae Caramelo',
            'cost_price' => 3000,
            'sale_price' => 10000,
            'stock_quantity' => 30,
        ]);
        $table = RestaurantTable::create([
            'number' => '3',
            'name' => 'Mesa 3 Ventana',
            'capacity' => 4,
        ]);

        // 1. Create Order
        $orderRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'table_id' => $table->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'notes' => 'Extra salsa de caramelo',
                    ]
                ]
            ]);

        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.id');
        $this->assertEquals(20000.00, $orderRes->json('data.total'));

        // 2. Checkout / Invoicing
        $checkoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/invoices', [
                'order_id' => $orderId,
                'customer_name' => 'Cliente Frecuente',
                'payments' => [
                    [
                        'payment_method' => 'cash',
                        'amount' => 10000,
                    ],
                    [
                        'payment_method' => 'card',
                        'amount' => 10000,
                        'reference_code' => 'VISA-789',
                    ]
                ],
                'billing_mode' => 'internal',
            ]);

        $checkoutRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.generation.success', true);

        // Verify order closed and table released
        $this->assertEquals('closed', Order::find($orderId)->status);
        $this->assertEquals('available', RestaurantTable::find($table->id)->status);
    }
}
