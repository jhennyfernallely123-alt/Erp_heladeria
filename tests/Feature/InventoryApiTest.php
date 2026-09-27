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
        $suffix = uniqid();

        $category = Category::create([
            'name' => 'Helados ' . $suffix,
            'slug' => 'helados-' . $suffix,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vaso ' . $suffix,
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
        // qty=30 es normal, qty=2 es bajo y qty=0 es crítico: una fila cuenta
        // en una sola categoría, nunca en varias.
        $this->assertEquals(1, $stats['low_count']);
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
