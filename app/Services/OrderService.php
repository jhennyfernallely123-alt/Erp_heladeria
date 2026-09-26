<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function createOrder(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad((Order::todayCount() + 1), 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'order_number' => $orderNumber,
                'table_id' => $data['table_id'] ?? null,
                'user_id' => $user->id,
                'type' => !empty($data['table_id']) ? 'dine_in' : ($data['type'] ?? 'takeaway'),
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
                'tip_amount' => $data['tip_amount'] ?? 0.00,
                'discount_total' => $data['discount_total'] ?? 0.00,
            ]);

            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'occupied']);
            }

            if (!empty($data['items'])) {
                $this->syncItems($order, $data['items']);
            }

            return $order->fresh(['items.product', 'items.variant', 'table']);
        });
    }

    public function syncItems(Order $order, array $itemsData): Order
    {
        return DB::transaction(function () use ($order, $itemsData) {
            // Restore previous stock if items were already deducted
            if ($order->status !== 'closed' && $order->status !== 'cancelled') {
                foreach ($order->items as $existingItem) {
                    $this->inventoryService->restoreStock($existingItem->product, $existingItem->variant, (float) $existingItem->quantity);
                }
            }

            $order->items()->delete();

            $subtotal = 0.00;

            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = !empty($item['product_variant_id']) ? ProductVariant::findOrFail($item['product_variant_id']) : null;
                $quantity = (float) $item['quantity'];

                $unitPrice = $variant ? (float) $variant->sale_price : (float) $product->sale_price;
                $itemSubtotal = $unitPrice * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Deduct stock
                $this->inventoryService->deductStock($product, $variant, $quantity);

                $subtotal += $itemSubtotal;
            }

            $order->subtotal = $subtotal;
            $order->total = max(0, $subtotal - (float) $order->discount_total + (float) $order->tip_amount + (float) $order->tax_total);
            $order->save();

            return $order->fresh(['items.product', 'items.variant']);
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->status = $status;
        $order->save();

        if (in_array($status, ['closed', 'cancelled']) && $order->table_id) {
            RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
        }

        if ($status === 'cancelled') {
            foreach ($order->items as $item) {
                $this->inventoryService->restoreStock($item->product, $item->variant, (float) $item->quantity);
            }
        }

        return $order;
    }
}
