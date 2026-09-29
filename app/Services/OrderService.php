<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected WorkShiftService $workShiftService,
    ) {}

    public function createOrder(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $orderNumber = 'ORD-'.now()->format('Ymd').'-'.str_pad((Order::todayCount() + 1), 4, '0', STR_PAD_LEFT);

            // Un pedido con mesa siempre es dine_in. Sin mesa, el tipo viene del
            // cliente (takeaway o delivery).
            $type = ! empty($data['table_id']) ? 'dine_in' : ($data['type'] ?? 'takeaway');
            $isDelivery = $type === 'delivery';

            $order = Order::create([
                'order_number' => $orderNumber,
                'table_id' => $data['table_id'] ?? null,
                'user_id' => $user->id,

                // A que turno se atribuye la comanda. El navegador manda el id
                // del turno activo del dispositivo, y el servicio lo valida: si
                // no sirve, queda en null y el pedido se guarda igual.
                'work_shift_id' => $this->workShiftService->resolveShiftId($user, $data['work_shift_id'] ?? null),

                'type' => $type,
                'status' => 'open',
                'notes' => $data['notes'] ?? null,

                // Los datos de entrega solo existen para un domicilio. En mesa o
                // para llevar se guardan en null aunque el cliente los mande.
                'delivery_name' => $isDelivery ? ($data['delivery_name'] ?? null) : null,
                'delivery_phone' => $isDelivery ? ($data['delivery_phone'] ?? null) : null,
                'delivery_address' => $isDelivery ? ($data['delivery_address'] ?? null) : null,
                'delivery_notes' => $isDelivery ? ($data['delivery_notes'] ?? null) : null,

                // La tarifa se lee una sola vez y se congela en el pedido. Si
                // manana se cambia en Configuracion, este pedido sigue
                // mostrando lo que realmente se cobro.
                'delivery_fee' => $isDelivery
                    ? (float) BusinessSetting::get('delivery_fee', 0)
                    : 0.00,

                'tip_amount' => $data['tip_amount'] ?? 0.00,
                'discount_total' => $data['discount_total'] ?? 0.00,
            ]);

            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'occupied']);
            }

            if (! empty($data['items'])) {
                $this->syncItems($order, $data['items']);
            }

            return $order->fresh(['items.product', 'items.variant', 'table', 'workShift']);
        });
    }

    public function syncItems(Order $order, array $itemsData): Order
    {
        return DB::transaction(function () use ($order, $itemsData) {
            // NO se toca stock acá. El inventario de la heladería es la lista de
            // insumos (Supply), que el admin cuenta a mano al cierre. Vender un
            // helado no descuenta leche ni fresa de ningún lado, y tocar el
            // stock por producto daba numeros que nunca cuadraban con el conteo
            // real. Ver SupplyService.
            $order->items()->delete();

            $subtotal = 0.00;

            foreach ($itemsData as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = ! empty($item['product_variant_id']) ? ProductVariant::findOrFail($item['product_variant_id']) : null;
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

                $subtotal += $itemSubtotal;
            }

            $order->subtotal = $subtotal;

            // La tarifa de envio no entra en el subtotal: se muestra como linea
            // propia del desglose para que el cliente vea cuanto es el envio.
            $order->total = max(
                0,
                $subtotal
                - (float) $order->discount_total
                + (float) $order->tip_amount
                + (float) $order->tax_total
                + (float) $order->delivery_fee
            );
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

        // Cancelar una comanda no restituye stock: ver el comentario de
        // syncItems. El conteo de insumos lo hace el admin al cierre.

        return $order;
    }
}
