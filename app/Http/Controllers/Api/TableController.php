<?php

namespace App\Http\Controllers\Api;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TableController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $tables = RestaurantTable::with(['activeOrder.items.product', 'activeOrder.items.variant', 'activeOrder.user'])
            ->orderBy('number')
            ->get();

        return $this->successResponse($tables);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'required|string|unique:restaurant_tables,number',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'in:available,occupied,reserved',
        ]);

        $table = RestaurantTable::create($validated);
        return $this->successResponse($table, 'Mesa creada exitosamente', 201);
    }

    public function show(RestaurantTable $table): JsonResponse
    {
        return $this->successResponse($table->load(['activeOrder.items.product', 'activeOrder.items.variant']));
    }

    public function update(Request $request, RestaurantTable $table): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|required|string|unique:restaurant_tables,number,' . $table->id,
            'name' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|in:available,occupied,reserved',
        ]);

        $table->update($validated);
        return $this->successResponse($table, 'Mesa actualizada exitosamente');
    }

    public function destroy(RestaurantTable $table): JsonResponse
    {
        if ($table->status === 'occupied') {
            return $this->errorResponse('No se puede eliminar una mesa que actualmente está ocupada.', 422);
        }

        $table->delete();
        return $this->successResponse(null, 'Mesa eliminada exitosamente');
    }
}
