<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->get();
        return $this->successResponse($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);
        return $this->successResponse($category, 'Categoría creada exitosamente', 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);
        return $this->successResponse($category, 'Categoría actualizada exitosamente');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return $this->successResponse(null, 'Categoría eliminada exitosamente');
    }
}
