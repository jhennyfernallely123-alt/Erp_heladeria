<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class InventoryService
{
    public function deductStock(Product $product, ?ProductVariant $variant, float $quantity): void
    {
        if ($variant) {
            $variant->decrement('stock_quantity', $quantity);
        } else {
            $product->decrement('stock_quantity', $quantity);
        }
    }

    public function restoreStock(Product $product, ?ProductVariant $variant, float $quantity): void
    {
        if ($variant) {
            $variant->increment('stock_quantity', $quantity);
        } else {
            $product->increment('stock_quantity', $quantity);
        }
    }

    public function adjustStock(Product $product, ?ProductVariant $variant, float $newQuantity, string $reason = ''): void
    {
        if ($variant) {
            $variant->update(['stock_quantity' => $newQuantity]);
        } else {
            $product->update(['stock_quantity' => $newQuantity]);
        }
    }

    public function getLowStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->with(['category', 'variants'])
            ->get();
    }
}
