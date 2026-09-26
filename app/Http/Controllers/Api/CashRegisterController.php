<?php

namespace App\Http\Controllers\Api;

use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CashRegisterController extends BaseApiController
{
    public function __construct(protected CashRegisterService $cashService)
    {
    }

    public function current(Request $request): JsonResponse
    {
        $session = $this->cashService->getCurrentSession($request->user());
        return $this->successResponse($session);
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
        $session = $this->cashService->getCurrentSession($request->user());
        if (!$session) {
            return $this->errorResponse('No tienes ningún turno de caja abierto.', 422);
        }

        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'category' => 'required|in:operating_expense,purchase,payroll,other',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $movement = $this->cashService->addMovement(
            $session,
            $request->user(),
            $validated['type'],
            (float) $validated['amount'],
            $validated['category'],
            $validated['description']
        );

        return $this->successResponse($movement, 'Movimiento de caja registrado exitosamente', 201);
    }

    public function close(Request $request): JsonResponse
    {
        $session = $this->cashService->getCurrentSession($request->user());
        if (!$session) {
            return $this->errorResponse('No tienes ningún turno de caja abierto.', 422);
        }

        $validated = $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $closed = $this->cashService->closeRegister($session, (float) $validated['actual_balance'], $validated['notes'] ?? null);

        return $this->successResponse($closed, 'Turno de caja cerrado exitosamente');
    }
}
