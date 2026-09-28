<?php

namespace App\Http\Controllers\Api;

use App\Models\TimeOffAttachment;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Vista de equipo del administrador.
 *
 * A diferencia del portal del empleado, aca si se usa el token de Sanctum: el
 * admin se loguea con email y contraseña y manages toda la información de
 * los empleados, INCLUDING las solicitudes que ellos enviaron.
 */
class TeamController extends BaseApiController
{
    public function __construct(
        protected EmployeeService $service,
        protected AttachmentService $attachments,
    ) {}

    /**
     * Listado del equipo con saldo de vacaciones y días Highlight. Los
     * empleados sin fecha de contratación salen marcados para que el admin
     * sepa que les falta cargarla.
     */
    public function index(): JsonResponse
    {
        $employees = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $balance = $this->service->vacationBalance($u);

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'phone' => $u->phone ?? null,
                    'email' => $u->email,
                    'roles' => $u->getRoleNames(),
                    'has_pin' => filled($u->pin),
                    'hired_at' => $u->hired_at?->toDateString(),
                    'birth_date' => $u->birth_date?->toDateString(),
                    // El dia de familia se expone dos veces: completo para
                    // editarlo en el modal, y como mes-dia para la tarjeta.
                    'family_day' => $u->family_day?->format('m-d'),
                    'family_day_full' => $u->family_day?->toDateString(),
                    'tenure' => $u->hired_at
                        ? $this->service->tenureLabel($u)
                        : null,
                    'vacation' => $balance,
                    'upcoming' => $this->service->upcoming($u),
                ];
            });

        return $this->successResponse($employees);
    }

    /**
     * Ficha completa de una persona, para que el admin vea lo mismo que ve el
     * empleado más su historial completo.
     */
    public function show(User $user): JsonResponse
    {
        return $this->successResponse($this->service->profile($user->load('roles')));
    }

    /**
     * Carga o corrige los datos de la ficha. hired_at es la que dispara el
     * cálculo de vacaciones, asi que es opcional acá y el service avisa si
     * falta.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'hired_at' => 'nullable|date',
            'birth_date' => 'nullable|date',
            'family_day' => 'nullable|date',
        ]);

        $user->update($validated);

        return $this->successResponse(
            $this->service->profile($user->fresh('roles')),
            'Ficha actualizada'
        );
    }

    /**
     * Solicitudes de todo el equipo. Por defecto solo las pendientes, que es lo
     * que el admin tiene que resolver; con ?status=all ve el historial.
     */
    public function requests(Request $request): JsonResponse
    {
        $status = $request->query('status', TimeOffRequest::STATUS_PENDING);

        $query = TimeOffRequest::with(['user', 'reviewer', 'attachments'])
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $this->successResponse(
            $query->get()->map(fn ($r) => $this->service->presentRequest($r) + [
                'user_name' => $r->user?->name,
            ])->all()
        );
    }

    /**
     * Aprueba o rechaza una solicitud. El saldo se recalcula en el momento de
     * la aprobacion, no contra el de la solicitud: si el empleado gastó otros
     * días mientras esperaba, el saldo actual es el que manda.
     */
    public function review(Request $request, TimeOffRequest $timeOff): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'response_note' => 'nullable|string|max:500',
        ]);

        try {
            $reviewd = $this->service->reviewRequest(
                $timeOff,
                $request->user(),
                $validated['status'],
                $validated['response_note'] ?? null
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        $message = $validated['status'] === TimeOffRequest::STATUS_APPROVED
            ? 'Solicitud aprobada'
            : 'Solicitud rechazada';

        return $this->successResponse(
            $this->service->presentRequest($reviewd->load('attachments')),
            $message
        );
    }

    /**
     * Descarga un documento adjunto de una solicitud.
     *
     * El admin puede verlos todos porque tiene manage_settings; el service
     * igual valida el permiso, para que la regla no quede solo en la ruta.
     */
    public function download(TimeOffAttachment $attachment)
    {
        try {
            $attachment = $this->attachments->findFor(request()->user(), $attachment);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return Storage::disk(AttachmentService::DISK)->download(
            $attachment->stored_path,
            $attachment->original_name
        );
    }
}
