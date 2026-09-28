<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkShift;
use App\Services\WorkShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WorkShiftController extends BaseApiController
{
    public function __construct(protected WorkShiftService $service)
    {
    }

    /**
     * Listado de meseros que pueden marcar turno. Solo para quien tenga
     * clock_shift: en un dispositivo compartido es quien cambia de persona.
     */
    public function workers(): JsonResponse
    {
        if (! request()->user()->can('clock_shift')) {
            return $this->errorResponse('No tienes permiso para ver el listado de meseros.', 403);
        }

        return $this->successResponse($this->service->workers());
    }

    /**
     * Turnos del dia. El mesero ve los suyos; el admin ve todos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkShift::with('user')
            ->whereBetween('opened_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderByDesc('opened_at');

        if (! $request->user()->can('manage_settings')) {
            $query->where('user_id', $request->user()->id);
        }

        $shifts = $query->get()->map(fn ($s) => $this->present($s));

        return $this->successResponse($shifts);
    }

    /** Un turno puntual, para que el dispositivo confirme si sigue abierto. */
    public function show(WorkShift $shift): JsonResponse
    {
        if (! $this->canTouch($shift)) {
            return $this->errorResponse('No tienes permiso para ver este turno.', 403);
        }

        return $this->successResponse($this->present($shift->load('user')));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->can('clock_shift')) {
            return $this->errorResponse('No tienes permiso para abrir turnos.', 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'pin' => 'required|string|size:4',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $shift = $this->service->open(
                $request->user(),
                (int) $validated['user_id'],
                (string) $validated['pin'],
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->present($shift->load('user')), 'Turno iniciado', 201);
    }

    public function close(Request $request, WorkShift $shift): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $shift = $this->service->close($shift, $request->user(), $validated['notes'] ?? null);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->present($shift->load('user')), 'Turno cerrado');
    }

    /** Resumen del dia por mesero: comandas, facturas, ventas y horas. */
    public function summary(Request $request): JsonResponse
    {
        if (! $request->user()->can('manage_settings')) {
            return $this->errorResponse('Solo el administrador puede ver el resumen de meseros.', 403);
        }

        return $this->successResponse($this->service->dailySummary($request->query('date')));
    }

    private function canTouch(WorkShift $shift): bool
    {
        $user = request()->user();

        return $shift->user_id === $user->id || $user->can('manage_settings');
    }

    private function present(WorkShift $shift): array
    {
        return [
            'id' => $shift->id,
            'status' => $shift->status,
            'opened_at' => $shift->opened_at->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'worked_minutes' => $shift->workedMinutes(),
            'notes' => $shift->notes,
            'user' => $shift->user ? [
                'id' => $shift->user->id,
                'name' => $shift->user->name,
            ] : null,
        ];
    }
}
