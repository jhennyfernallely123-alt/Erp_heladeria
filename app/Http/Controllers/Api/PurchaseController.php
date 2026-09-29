<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Models\Supply;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Lista de compras del día siguiente.
 *
 * El admin la arma desde Inventario con un botón por insumo, y acá la ve como
 * lista. Al marcar una compra como hecha, la cantidad se suma al stock del
 * insumo.
 */
class PurchaseController extends BaseApiController
{
    public function __construct(protected PurchaseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $purchases = $this->service->index($request->query('status', Purchase::STATUS_PENDING));

        return $this->successResponse(
            $this->service->presentMany($purchases),
            'Compras consultadas',
            200,
            ['stats' => $this->service->stats($purchases)]
        );
    }

    /** Pasa un insumo a la lista de compras. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supply_id' => 'required|integer|exists:supplies,id',
            'quantity' => 'required|numeric|min:0.001',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $purchase = $this->service->request(
                Supply::findOrFail($validated['supply_id']),
                (float) $validated['quantity'],
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(
            $this->service->present($purchase),
            'Pasado a la lista de compras',
            201
        );
    }

    /** Marca la compra como hecha y suma el stock. */
    public function markBought(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'received_quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            $bought = $this->service->markAsBought(
                $purchase,
                $request->user(),
                isset($validated['received_quantity'])
                    ? (float) $validated['received_quantity']
                    : null
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(
            $this->service->present($bought),
            'Compra marcada como hecha'
        );
    }

    /** Cancela una compra pendiente y descuenta del stock. */
    public function cancel(Purchase $purchase): JsonResponse
    {
        try {
            $this->service->cancel($purchase);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(null, 'Compra cancelada');
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'note' => 'nullable|string|max:255',
        ]);

        if (! $purchase->isPending()) {
            return $this->errorResponse('Solo se pueden editar compras pendientes.', 422);
        }

        $purchase->update([
            'quantity' => (float) $validated['quantity'],
            'note' => $validated['note'] ?? null,
        ]);

        return $this->successResponse(
            $this->service->present($purchase->fresh(['supply', 'requester'])),
            'Compra actualizada'
        );
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        if (! $purchase->isPending()) {
            return $this->errorResponse(
                'La compra ya está marcada como hecha y no se puede borrar. Marcá la compra como no hecha si hubo error.',
                422
            );
        }

        $this->service->cancel($purchase);

        return $this->successResponse(null, 'Compra eliminada');
    }
}
