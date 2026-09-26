<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BusinessSetting;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\InternalInvoiceProvider;
use App\Services\Invoicing\DianInvoiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class InvoicingProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_internal_invoice_provider_generates_consecutive_and_pdf(): void
    {
        $user = User::create(['name' => 'Cashier', 'email' => 'caja@test.com', 'password' => bcrypt('123')]);
        $table = RestaurantTable::create(['number' => '1', 'name' => 'Mesa 1']);
        $cat = Category::create(['name' => 'Helados', 'slug' => 'helados']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Cono Doble',
            'cost_price' => 2000,
            'sale_price' => 7000,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'table_id' => $table->id,
            'user_id' => $user->id,
            'subtotal' => 7000,
            'total' => 7000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 7000,
            'subtotal' => 7000,
        ]);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'TEMP-001',
            'subtotal' => 7000,
            'total' => 7000,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cash',
            'amount' => 7000,
        ]);

        $provider = InvoiceService::getProvider('internal');
        $this->assertInstanceOf(InternalInvoiceProvider::class, $provider);

        $result = $provider->generateInvoice($invoice);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('FAC-', $result['invoice_number']);
        $this->assertEquals('accepted', $result['dian_status']);
        Storage::disk('public')->assertExists($result['pdf_path']);
    }

    public function test_dian_invoice_provider_generates_cufe_and_pdf(): void
    {
        $user = User::create(['name' => 'Cashier', 'email' => 'caja2@test.com', 'password' => bcrypt('123')]);
        $cat = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Malteada Vainilla',
            'cost_price' => 3000,
            'sale_price' => 12000,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-002',
            'user_id' => $user->id,
            'subtotal' => 12000,
            'total' => 12000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 12000,
            'subtotal' => 12000,
        ]);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'TEMP-002',
            'customer_name' => 'Empresa SAS',
            'customer_doc_type' => 'NIT',
            'customer_doc_number' => '901234567',
            'subtotal' => 12000,
            'total' => 12000,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'card',
            'amount' => 12000,
            'reference_code' => 'CARD-123456',
        ]);

        $provider = InvoiceService::getProvider('dian');
        $this->assertInstanceOf(DianInvoiceProvider::class, $provider);

        $result = $provider->generateInvoice($invoice);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['cufe']);
        $this->assertEquals(96, strlen($result['cufe'])); // SHA-384 string length
        Storage::disk('public')->assertExists($result['pdf_path']);
    }
}
