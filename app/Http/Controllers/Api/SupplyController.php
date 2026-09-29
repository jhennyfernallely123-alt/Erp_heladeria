<?php

namespace App\Http\Controllers\Api;

use App\Models\Supply;
use App\Services\SupplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Insumos de la heladería.
 *
 * Reemplaza al inventario por producto: acá no se descuenta nada solo, el admin
 * cuenta lo que queda al cierre y lo carga. Es un conteo diario, no un stock
 * teórico.
 */
class SupplyController extends BaseApiController
{
    public function __construct(protected SupplyService $service) {}

    public function index(Request $request): JsonResponse
    {
        $supplies = $this->service->index(
            $request->filled('search') ? trim($request->search) : null
        );

        return $this->successResponse(
            $this->service->presentMany($supplies),
            'Insumos consultados',
            200,
            ['stats' => $this->service->stats($supplies)]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'unit' => 'required|in:'.implode(',', Supply::UNITS),
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $supply = $this->service->create($validated, $request->user());
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->service->present($supply), 'Insumo agregado', 201);
    }

    public function update(Request $request, Supply $supply): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'unit' => 'sometimes|required|in:'.implode(',', Supply::UNITS),
            'quantity' => 'sometimes|required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $updated = $this->service->update($supply, $validated);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->service->present($updated), 'Insumo actualizado');
    }

    /** Cambia la cantidad del insumo: reemplaza el valor anterior. */
    public function count(Request $request, Supply $supply): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $updated = $this->service->updateQuantity(
                $supply,
                (float) $validated['quantity'],
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->service->present($updated), 'Cantidad actualizada');
    }

    public function destroy(Supply $supply): JsonResponse
    {
        $this->service->delete($supply);

        return $this->successResponse(null, 'Insumo eliminado');
    }
}
