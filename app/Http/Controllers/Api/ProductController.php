<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'variants']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();

        return $this->successResponse($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules(true));

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($validated);
            $this->syncVariants($product, $validated['variants'] ?? []);
            ProductStock::resolve($product);

            return $product;
        });

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Producto creado exitosamente',
            201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse($product->load(['category', 'variants']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate($this->rules(false));

        DB::transaction(function () use ($product, $validated) {
            $product->update($validated);

            if (array_key_exists('variants', $validated)) {
                $product->variants()->delete();
                $this->syncVariants($product, $validated['variants'] ?? []);
            }
        });

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Producto actualizado exitosamente'
        );
    }

    public function toggleActive(Product $product): JsonResponse
    {
        $product->update(['is_active' => !$product->is_active]);

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            $product->is_active ? 'Producto activado' : 'Producto desactivado'
        );
    }

    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image' => $path]);

        return $this->successResponse(
            $product->fresh(['category', 'variants']),
            'Imagen actualizada exitosamente'
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->successResponse(null, 'Producto eliminado exitosamente');
    }

    private function rules(bool $required): array
    {
        $must = $required ? 'required' : 'sometimes|required';

        return [
            'category_id' => "{$must}|exists:categories,id",
            'name' => "{$must}|string|max:255",
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:255',
            'cost_price' => "{$must}|numeric|min:0",
            'sale_price' => "{$must}|numeric|min:0",
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.cost_price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'required|numeric|min:0',
        ];
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            $created = $product->variants()->create([
                'name' => $variant['name'],
                'cost_price' => $variant['cost_price'],
                'sale_price' => $variant['sale_price'],
            ]);

            ProductStock::resolve($product, $created);
        }
    }
}
