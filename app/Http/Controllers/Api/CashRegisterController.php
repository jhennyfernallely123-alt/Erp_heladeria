<?php

namespace App\Http\Controllers\Api;

use App\Services\CashRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashRegisterController extends BaseApiController
{
    public function __construct(protected CashRegisterService $cashService) {}

    public function current(Request $request): JsonResponse
    {
        $session = $this->cashService->getCurrentSession($request->user());

        return $this->successResponse($session);
    }

    /**
     * Turno abierto de la gaveta, sea de quien sea. El admin necesita ver el
     * del cajero para saber quien la tiene.
     */
    public function openSession(Request $request): JsonResponse
    {
        return $this->successResponse($this->cashService->getOpenSession());
    }

    /**
     * Saldo en vivo de la gaveta, con la caja abierta o cerrada.
     */
    public function balance(Request $request): JsonResponse
    {
        return $this->successResponse($this->cashService->getDrawerBalance());
    }

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $session = $this->cashService->openRegister($request->user(), (float) $validated['opening_balance'], $validated['notes'] ?? null);

            return $this->successResponse($session, 'Turno de caja abierto exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function addMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'category' => 'required|in:operating_expense,purchase,payroll,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        // Va sobre el turno abierto de la gaveta, no sobre el del usuario: la
        // gaveta es del local, y el admin registra gastos menores sobre el
        // turno que tiene el cajero.
        $session = $this->cashService->getOpenSession();
        if (! $session) {
            return $this->errorResponse('No hay turno de caja abierto. Para mover efectivo con la gaveta cerrada usá un retiro de gaveta.', 422);
        }

        try {
            $movement = $this->cashService->addMovement(
                $session,
                $request->user(),
                $validated['type'],
                (float) $validated['amount'],
                $validated['category'],
                $validated['description']
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($movement, 'Movimiento de caja registrado exitosamente', 201);
    }

    public function close(Request $request): JsonResponse
    {
        $session = $this->cashService->getOpenSession();
        if (! $session) {
            return $this->errorResponse('No hay ningún turno de caja abierto.', 422);
        }

        $validated = $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $closed = $this->cashService->closeRegister($session, (float) $validated['actual_balance'], $validated['notes'] ?? null);

        return $this->successResponse($closed, 'Turno de caja cerrado exitosamente');
    }

    /**
     * Resumen de caja del administrador: saldo de la gaveta, ingresos y
     * egresos del dia, movimientos y arqueos recientes.
     */
    public function overview(Request $request): JsonResponse
    {
        return $this->successResponse($this->cashService->getOverview());
    }

    public function history(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 30), 100);

        return $this->successResponse($this->cashService->getHistory($limit > 0 ? $limit : 30));
    }

    /**
     * Retiro de gaveta. A diferencia del movimiento, no exige turno abierto.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:payroll,vacation,utilities,merchandise,other',
            'reason' => 'required|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $withdrawal = $this->cashService->withdraw(
                $request->user(),
                (float) $validated['amount'],
                $validated['category'],
                $validated['reason'],
                $validated['receipt_number'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($withdrawal, 'Retiro de gaveta registrado exitosamente', 201);
    }
}
