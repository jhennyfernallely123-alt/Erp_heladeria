<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Movimientos de stock por producto y variante.
 *
 * YA NO SE USA. El inventario de la heladería pasó a ser la lista de insumos
 * (Supply / SupplyService), que es un conteo manual del cierre y no un stock
 * que se descuenta solo.
 *
 * Se conserva el servicio y el controller por si hay que volver atrás, pero
 * OrderService ya no llama a deductStock ni a restoreStock: vender no toca
 * ningún stock. Ver OrderService.
 */
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

    public function adjustStock(
        Product $product,
        ?ProductVariant $variant,
        float $newQuantity,
        string $reason = '',
        ?int $userId = null,
        string $movementType = 'adjustment'
    ): void {
        $stock = ProductStock::resolve($product, $variant);
        $delta = round($newQuantity - (float) $stock->quantity, 2);

        $stock->update(['quantity' => $newQuantity]);

        if ($delta !== 0.0) {
            $stock->movements()->create([
                'type' => $movementType,
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
