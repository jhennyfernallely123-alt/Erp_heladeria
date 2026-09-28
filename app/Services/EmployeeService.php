<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Portal del empleado: ficha, saldo de vacaciones y solicitudes de tiempo
 * libre.
 *
 * La diferencia clave con el resto del sistema es que aca la persona NO se
 * loguea: elige su nombre e ingresa su PIN, como en el modulo de turnos. Por
 * eso todo lo que sale por aca tiene que estar acotado a la persona que se
 * identifico, y el PIN es la unica frontera de seguridad.
 */
class EmployeeService
{
    /**
     * Alcance del contador de intentos del portal. Distinto del de turnos: fallar
     * al marcar turno no debe agotar los intentos de abrir la ficha.
     */
    public const PIN_SCOPE = 'employee_portal';

    /** Dias de vacaciones por cada mes completo trabajado. 12 x 1,25 = 15 al anio. */
    public const DAYS_PER_MONTH = 1.25;

    /** Tope legal anual. Los dias sobrantes si se arrastran al siguiente anio. */
    public const DAYS_PER_YEAR = 15;

    /**
     * Motivos que puede pedir un empleado.
     *
     * De estos, solo 'vacation' descuenta el saldo de vacaciones. Los medicos
     * (sick_leave, incapacity) y los dias ya establecidos (family_day,
     * birthday) van aparte, y 'unpaid' es permiso sin sueldo.
     */
    public const REQUEST_TYPES = [
        'vacation',
        'family_day',
        'birthday',
        'unpaid',
        'sick_leave',
        'incapacity',
    ];

    /** Motivos que se descuentan del saldo de vacaciones. */
    private const DEDUCTS_VACATION = ['vacation'];

    /**
     * Motivos que siempre valen un solo día. La incapacidad NO está acá: el
     * médico puede darla por varios días seguidos, así que se cuenta en días
     * hábiles del rango. Igual no descuenta vacaciones, porque va con su
     * incapacidad y por eso no toca el saldo.
     */
    private const SINGLE_DAY_TYPES = [
        'family_day',
        'birthday',
        'sick_leave',
    ];

    public function __construct(protected PinAuthService $pinAuth) {}

    /**
     * Empleados que pueden entrar al portal: cualquiera con PIN configurado,
     * sin importar el rol. Meseros, cocina, cajero y admin son empleados.
     */
    public function employees(): Collection
    {
        return User::with('roles')
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone ?? null,
                'roles' => $u->getRoleNames(),
            ]);
    }

    /**
     * Valida el PIN de un empleado y lo devuelve identificado.
     *
     * Se separa de profile() a proposito: autenticar y leer son dos pasos. El
     * controller usa esto para abrir la sesion del portal y despues arma la
     * ficha con profile().
     *
     * @throws RuntimeException
     */
    public function authenticate(int $employeeId, string $pin): User
    {
        $employee = User::with('roles')->find($employeeId);

        if (! $employee || blank($employee->pin)) {
            // Mismo mensaje que un PIN incorrecto: el portal no debe revelar
            // que nombres existen ni quienes tienen PIN.
            $this->pinAuth->registerFailedAttempt($employeeId, self::PIN_SCOPE);

            throw new RuntimeException($this->pinAuth->invalidMessage());
        }

        $this->pinAuth->verify($employee, $pin, self::PIN_SCOPE);

        return $employee;
    }

    /**
     * Ficha completa de una persona: datos, saldos, proximos eventos e
     * historial de solicitudes.
     */
    public function profile(User $employee): array
    {
        $balance = $this->vacationBalance($employee);

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'phone' => $employee->phone ?? null,
            'roles' => $employee->getRoleNames(),
            'hired_at' => $employee->hired_at?->toDateString(),
            'birth_date' => $employee->birth_date?->toDateString(),
            'family_day' => $employee->family_day?->format('m-d'),
            'tenure' => $this->tenure($employee),
            'vacation' => $balance,
            'upcoming' => $this->upcoming($employee),
            'requests' => $this->requestsFor($employee),
        ];
    }

    /**
     * Saldo de vacaciones: se acumula solo, no se carga a mano.
     *
     * La regla es 1,25 dias por cada mes completo trabajado desde la fecha de
     * contratacion. A los 6 meses da 7,5 y a los 12 meses da 15. Los dias que
     * el empleado no usa se suman al anio siguiente, de modo que el saldo
     * nunca se pierde: sale de la antiguedad menos lo ya tomado.
     */
    public function vacationBalance(User $employee): array
    {
        $hiredAt = $employee->hired_at;

        if (! $hiredAt) {
            // Sin fecha de contratacion no hay nada que calcular. El admin la
            // carga desde la vista de equipo.
            return [
                'available' => false,
                'accrued' => 0.0,
                'used' => 0.0,
                'available_days' => 0.0,
                'months_worked' => 0,
                'message' => 'Falta cargar la fecha de contratación para calcular el saldo.',
            ];
        }

        $monthsWorked = $this->fullMonthsWorked($hiredAt);

        // El acumulado no tiene tope artificial: 1,25 por mes completado. Asi el
        // arrastre de dias sobrantes es la consecuencia natural de la formula y
        // no un calculo aparte que se pueda desincronizar.
        $accrued = round($monthsWorked * self::DAYS_PER_MONTH, 2);

        $used = (float) TimeOffRequest::where('user_id', $employee->id)
            ->where('status', TimeOffRequest::STATUS_APPROVED)
            ->where('type', 'vacation')
            ->sum('days');

        $available = round($accrued - $used, 2);

        return [
            'available' => true,
            'accrued' => $accrued,
            'used' => $used,
            'available_days' => $available,
            'months_worked' => $monthsWorked,
            'per_month' => self::DAYS_PER_MONTH,
            'per_year' => self::DAYS_PER_YEAR,
        ];
    }

    /**
     * Meses completos trabajados a la fecha de hoy.
     *
     * Se cuentan meses calendario cerrados: del 15 de enero al 14 de febrero
     * son cero meses completos, porque el segundo mes no termina. Es lo que
     * hace que el proporcional sea exacto y no una aproximacion.
     */
    public function fullMonthsWorked(Carbon $hiredAt, ?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $start = $hiredAt->copy()->startOfDay();

        if ($asOf->lt($start)) {
            return 0;
        }

        $months = ($asOf->year - $start->year) * 12 + ($asOf->month - $start->month);

        // El mes en curso solo cuenta si ya se cumplio el dia de corte: el dia
        // de la hiring.
        if ($asOf->day < $start->day) {
            $months--;
        }

        return max(0, $months);
    }

    /**
     * Dias habiles entre dos fechas, sin domingos ni festivos.
     *
     * Un dia habil es lunes a viernes que no sea festivo. Los festivos salen
     * de BusinessSetting, que ya es la tabla clave/valor del proyecto.
     */
    public function businessDays(Carbon $start, Carbon $end): float
    {
        if ($end->lt($start)) {
            return 0.0;
        }

        $holidays = $this->holidays();
        $days = 0.0;
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            if ($cursor->isWeekday() && ! in_array($cursor->toDateString(), $holidays, true)) {
                $days++;
            }
            $cursor->addDay();
        }

        return (float) $days;
    }

    /** Feriados registrados en ajustes, en formato YYYY-MM-DD. */
    private function holidays(): array
    {
        $raw = BusinessSetting::get('holidays', '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($d) => is_string($d) ? substr($d, 0, 10) : null,
            $decoded
        )));
    }

    /**
     * Proximos cumpleaños y dia de familia, para que el empleado vea cuando
     * tiene el dia libre y el admin vea a quien le toca.
     */
    public function upcoming(User $employee): array
    {
        $today = now()->startOfDay();
        $items = [];

        if ($employee->birth_date) {
            $birthday = $this->nextAnniversary($employee->birth_date, $today);
            $items[] = [
                'type' => 'birthday',
                'label' => 'Cumpleaños',
                'date' => $birthday->toDateString(),
                'days_away' => (int) $today->diffInDays($birthday, false),
            ];
        }

        if ($employee->family_day) {
            $family = $this->nextAnniversary($employee->family_day, $today);
            $items[] = [
                'type' => 'family_day',
                'label' => 'Día de familia',
                'date' => $family->toDateString(),
                'days_away' => (int) $today->diffInDays($family, false),
            ];
        }

        usort($items, fn ($a, $b) => $a['days_away'] <=> $b['days_away']);

        return $items;
    }

    /**
     * Proxima fecha en que se cumple un dia de una fecha de origen (cumpleanos
     * o dia de familia). El dia de familia se guarda como fecha completa, pero
     * solo importan el mes y el dia.
     */
    private function nextAnniversary(Carbon $origin, Carbon $today): Carbon
    {
        $candidate = $today->copy()->setDate($today->year, $origin->month, min($origin->day, 28));

        if ($candidate->lt($today)) {
            $candidate = $candidate->copy()->addYear();
        }

        return $candidate;
    }

    /** Antiguedad legible: "1 anio 3 meses". */
    public function tenureLabel(User $employee): string
    {
        return $this->tenure($employee)['label'];
    }

    private function tenure(User $employee): array
    {
        if (! $employee->hired_at) {
            return ['years' => 0, 'months' => 0, 'label' => 'Sin fecha de contratación'];
        }

        $months = $this->fullMonthsWorked($employee->hired_at);
        $years = intdiv($months, 12);
        $rest = $months % 12;

        $parts = [];
        if ($years > 0) {
            $parts[] = $years.($years === 1 ? ' año' : ' años');
        }
        if ($rest > 0) {
            $parts[] = $rest.($rest === 1 ? ' mes' : ' meses');
        }

        return [
            'years' => $years,
            'months' => $rest,
            'label' => $parts ? implode(' y ', $parts) : 'Menos de un mes',
        ];
    }

    /** Historial de solicitudes de una persona, mas reciente primero. */
    public function requestsFor(User $employee): array
    {
        return TimeOffRequest::with(['reviewer', 'attachments'])
            ->where('user_id', $employee->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => $this->presentRequest($r))
            ->all();
    }

    /**
     * El empleado solicita tiempo libre sobre días que marcó en un calendario.
     *
     * Recibe un lote de tramos en vez de un rango único, porque desde el
     * calendario cada día se puede marcar con un tipo distinto: vacaciones, día
     * de familia o cumpleaños. Cada elemento trae {start_date, end_date, type}.
     *
     * El saldo NO se descuenta acá: se descuenta al aprobar. Si se descontara al
     * solicitar, un rechazo dejaría el saldo mal y el empleado tendría que
     * reclamarlo.
     */
    public function requestTimeOff(User $employee, array $items, ?string $reason = null): array
    {
        if (empty($items)) {
            throw new RuntimeException('Elegí al menos un día en el calendario.');
        }

        $validated = $this->validateSegments($items);

        $this->assertNoOverlap($employee, $validated);

        // El saldo se valida sobre el total de vacaciones de todo el lote, no
        // tramo por tramo: pedir 8 dias en dos tramos de 4 no puede pasar si
        // solo tiene 5 disponibles.
        $totalVacation = 0.0;

        foreach ($validated as $item) {
            if (in_array($item['type'], self::DEDUCTS_VACATION, true)) {
                $totalVacation += $item['days'];
            }
        }

        if ($totalVacation > 0) {
            $available = $this->vacationBalance($employee)['available_days'];

            if ($totalVacation > $available) {
                throw new RuntimeException(
                    "Pedís {$totalVacation} días de vacaciones y tenés {$available} disponibles."
                );
            }
        }

        $created = [];

        foreach ($validated as $item) {
            $created[] = TimeOffRequest::create([
                'user_id' => $employee->id,
                'type' => $item['type'],
                'start_date' => $item['start']->toDateString(),
                'end_date' => $item['end']->toDateString(),
                'days' => $item['days'],
                'status' => TimeOffRequest::STATUS_PENDING,
                'reason' => $reason,
            ]);
        }

        return $created;
    }

    /**
     * Valida cada tramo del calendario y calcula sus días.
     *
     * Se validan TODOS antes de crear ninguno: si el segundo tramo es inválido,
     * el primero no debe quedar a medias.
     *
     * @return array<int, array{type: string, start: Carbon, end: Carbon, days: float}>
     */
    private function validateSegments(array $items): array
    {
        $validated = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? 'vacation';

            if (! in_array($type, self::REQUEST_TYPES, true)) {
                throw new RuntimeException('Tipo de solicitud no válido.');
            }

            $start = Carbon::parse($item['start_date'])->startOfDay();
            $end = Carbon::parse($item['end_date'] ?? $item['start_date'])->startOfDay();

            if ($end->lt($start)) {
                throw new RuntimeException('Hay un tramo con la fecha final anterior a la inicial.');
            }

            if ($start->lt(now()->startOfDay())) {
                throw new RuntimeException('No se pueden solicitar días que ya pasaron.');
            }

            // Los motivos de un solo día no se estiran: si el empleado marcó un
            // rango con uno de estos, el tramo cuenta como un día, porque lo que
            // está marcando es ese día en sí y no un periodo de vacaciones.
            $days = in_array($type, self::SINGLE_DAY_TYPES, true)
                ? 1.0
                : $this->businessDays($start, $end);

            if ($days <= 0) {
                throw new RuntimeException(
                    'Uno de los tramos no tiene días hábiles: son solo domingos o festivos.'
                );
            }

            $validated[] = [
                'type' => $type,
                'start' => $start,
                'end' => $end,
                'days' => $days,
            ];
        }

        return $validated;
    }

    /**
     * Ningún tramo puede pisar a otro ya pedido, ni a otro del mismo lote.
     */
    private function assertNoOverlap(User $employee, array $validated): void
    {
        $existing = TimeOffRequest::where('user_id', $employee->id)
            ->whereIn('status', [TimeOffRequest::STATUS_PENDING, TimeOffRequest::STATUS_APPROVED])
            ->get(['start_date', 'end_date']);

        foreach ($validated as $item) {
            $clashes = $existing->contains(
                fn ($r) => Carbon::parse($r->start_date)->lte($item['end'])
                    && Carbon::parse($r->end_date)->gte($item['start'])
            );

            if ($clashes) {
                throw new RuntimeException(
                    'Ya tenés una solicitud pendiente o aprobada que se superpone con esas fechas.'
                );
            }
        }

        foreach ($validated as $index => $item) {
            foreach ($validated as $other => $candidate) {
                if ($other <= $index) {
                    continue;
                }

                if ($item['start']->lte($candidate['end']) && $item['end']->gte($candidate['start'])) {
                    throw new RuntimeException('Los días marcados se superponen entre sí.');
                }
            }
        }
    }

    /**
     * El admin aprueba o rechaza una solicitud.
     *
     * El saldo se recalcula contra el momento de la aprobacion, no contra el de
     * la solicitud: si el empleado pidio 10 dias, gasto otros 5 mientras
     * esperaba, y le aprobaron los 10, el saldo quedaria en negativo. Ahi
     * preferimos rechazar con un mensaje claro.
     */
    public function reviewRequest(TimeOffRequest $request, User $reviewer, string $status, ?string $note = null): TimeOffRequest
    {
        if (! $request->isPending()) {
            throw new RuntimeException('Esta solicitud ya fue revisada.');
        }

        // El saldo solo se revalida si lo que se aprueba descuenta vacaciones.
        // Un permiso por salud o una incapacidad no tocan el saldo.
        $deducts = in_array($request->type, self::DEDUCTS_VACATION, true);

        if ($status === TimeOffRequest::STATUS_APPROVED && $deducts) {
            $employee = $request->user;
            $available = $this->vacationBalance($employee)['available_days'];

            if ((float) $request->days > $available) {
                throw new RuntimeException(
                    "No se puede aprobar: el empleado pidió {$request->days} días y ".
                        "solo tiene {$available} disponibles hoy."
                );
            }
        }

        $request->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'response_note' => $note,
        ]);

        return $request->fresh(['reviewer', 'user']);
    }

    public function presentRequest(TimeOffRequest $r): array
    {
        return [
            'id' => $r->id,
            'type' => $r->type,
            'start_date' => Carbon::parse($r->start_date)->toDateString(),
            'end_date' => Carbon::parse($r->end_date)->toDateString(),
            'days' => (float) $r->days,
            'status' => $r->status,
            'reason' => $r->reason ?? null,
            'response_note' => $r->response_note ?? null,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'reviewer_name' => $r->reviewer?->name ?? null,
            'requires_evidence' => app(AttachmentService::class)->requiresEvidence($r->type),
            'attachments' => app(AttachmentService::class)
                ->presentMany($r->attachments ?? collect()),
        ];
    }
}
