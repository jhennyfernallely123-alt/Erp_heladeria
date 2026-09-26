<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseApiController
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'variants']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock_alert');
        }

        $products = $query->orderBy('name')->get();

        return $this->successResponse($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_type' => 'required|in:unit,bulk_grams',
            'stock_quantity' => 'required|numeric|min:0',
            'min_stock_alert' => 'required|numeric|min:0',
            'has_variants' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.cost_price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'required|numeric|min:0',
            'variants.*.stock_quantity' => 'required|numeric|min:0',
        ]);

        $product = Product::create($validated);

        if (!empty($validated['has_variants']) && !empty($validated['variants'])) {
            foreach ($validated['variants'] as $v) {
                $product->variants()->create($v);
            }
        }

        return $this->successResponse($product->fresh(['category', 'variants']), 'Producto creado exitosamente', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse($product->load(['category', 'variants']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'sometimes|required|numeric|min:0',
            'stock_type' => 'sometimes|required|in:unit,bulk_grams',
            'stock_quantity' => 'sometimes|required|numeric|min:0',
            'min_stock_alert' => 'sometimes|required|numeric|min:0',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'variants' => 'nullable|array',
        ]);

        $product->update($validated);

        if (isset($validated['variants'])) {
            $product->variants()->delete();
            foreach ($validated['variants'] as $v) {
                $product->variants()->create($v);
            }
        }

        return $this->successResponse($product->fresh(['category', 'variants']), 'Producto actualizado exitosamente');
    }

    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $variant = !empty($validated['product_variant_id'])
            ? ProductVariant::findOrFail($validated['product_variant_id'])
            : null;

        $this->inventoryService->adjustStock($product, $variant, (float) $validated['new_quantity'], $validated['reason'] ?? '');

        return $this->successResponse($product->fresh(['variants']), 'Stock ajustado exitosamente');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return $this->successResponse(null, 'Producto eliminado exitosamente');
    }
}
