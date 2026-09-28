<?php

namespace App\Http\Controllers\Api;

use App\Models\TimeOffAttachment;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\EmployeePortalSession;
use App\Services\EmployeeService;
use App\Services\PinAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Portal del empleado.
 *
 * Estas rutas cuelgan de su propio middleware, no de auth:sanctum. La
 * diferencia importa: el PIN de 4 digitos abre una sesion corta que solo da
 * acceso a la ficha de quien la abrio, nunca a configuracion, caja ni POS.
 *
 * Ademas el listado de nombres cuelga de una sesion de lectura publica (sin
 * token) justamente para que el empleado no tenga que\loguearse con email y
 * contraseña: elige su nombre e ingresa su PIN. Ese listado solo devuelve
 * nombre y rol, que es lo unico que hace falta para elegir a quien sos.
 */
class EmployeeController extends BaseApiController
{
    public function __construct(
        protected EmployeeService $service,
        protected PinAuthService $pinAuth,
        protected EmployeePortalSession $sessions,
        protected AttachmentService $attachments,
    ) {}

    /**
     * Listado de empleados que pueden entrar al portal. Solo nombre y rol: no
     * expone contratacion, telefono ni nada de la ficha.
     */
    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->employees());
    }

    /**
     * Abre la ficha validando el PIN y devuelve una sesion del portal.
     *
     * El token que sale de aca NO es un token de Sanctum: no sirve para
     * autenticarse contra el resto de la API.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'pin' => 'required|string|size:4|digits:4',
        ]);

        try {
            $employee = $this->service->authenticate(
                (int) $validated['user_id'],
                (string) $validated['pin']
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'portal_token' => $this->sessions->issue($employee),
            'expires_in_minutes' => EmployeePortalSession::TTL_MINUTES,
            'profile' => $this->service->profile($employee),
        ], 'Bienvenido');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->sessions->forget($request->header('X-Portal-Token') ?: $request->bearerToken());

        return $this->successResponse(null, 'Sesión cerrada');
    }

    /** Ficha del empleado que abrio la sesion. */
    public function profile(Request $request): JsonResponse
    {
        return $this->successResponse($this->service->profile($this->employee($request)));
    }

    /** Solicitudes de esa persona. */
    public function requests(Request $request): JsonResponse
    {
        return $this->successResponse($this->service->requestsFor($this->employee($request)));
    }

    /**
     * Crea una o varias solicitudes de tiempo libre, con su evidencia.
     *
     * El empleado marca días sueltos en un calendario y cada tramo puede tener
     * un tipo distinto, así que se manda una lista. Los documentos van aparte,
     * en `documents[]`, y se asocian a la primera solicitud.
     *
     * El user_id sale siempre de la sesión del portal, nunca del cuerpo.
     */
    public function storeRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.start_date' => 'required|date',
            'items.*.end_date' => 'nullable|date|after_or_equal:items.*.start_date',
            'items.*.type' => ['required', 'in:'.implode(',', EmployeeService::REQUEST_TYPES)],
            'reason' => 'nullable|string|max:500',
            'documents' => 'nullable|array|max:3',
            'documents.*' => 'file|max:8192',
        ]);

        $employee = $this->employee($request);

        // Todo o nada. Si la solicitud se crea pero el documento falla la
        // validación, sin transaccion quedaria una solicitud sin respaldo
        // dando vueltas en la bandeja del admin, sin forma de saber que
        // faltó el archivo.
        try {
            $created = DB::transaction(function () use ($request, $employee, $validated) {
                $created = $this->service->requestTimeOff(
                    $employee,
                    $validated['items'],
                    $validated['reason'] ?? null
                );

                // Los documentos se asocian a la primera solicitud. El
                // calendario manda un tramo por motivo, asi que cuando llegan
                // varias es siempre el mismo tipo.
                $this->attachments->storeFor(
                    $created[0],
                    $request->file('documents', []),
                    $created[0]->type
                );

                return $created;
            });
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(
            array_map(fn ($r) => $this->service->presentRequest($r->load('attachments')), $created),
            count($created) > 1
                ? count($created).' solicitudes enviadas. El administrador las va a revisar.'
                : 'Solicitud enviada. El administrador la va a revisar.',
            201
        );
    }

    /**
     * Descarga un documento adjunto.
     *
     * Solo el dueño de la solicitud o el administrador. Los archivos se sirven
     * por acá y no por URL pública: una cédula en public/ sería un documento de
     * identidad expuesto a cualquiera que sepa la dirección.
     */
    public function download(Request $request, TimeOffAttachment $attachment)
    {
        try {
            $attachment = $this->attachments->findFor($this->employee($request), $attachment);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return Storage::disk(AttachmentService::DISK)->download(
            $attachment->stored_path,
            $attachment->original_name
        );
    }

    /**
     * El empleado de la sesion del portal. Viene del middleware, no del token
     * de Sanctum, y ese es justamente el punto.
     */
    private function employee(Request $request): User
    {
        $employee = $request->attributes->get('portal_employee');

        abort_unless($employee instanceof User, 401, 'Sesión de portal inválida.');

        return $employee;
    }
}
