<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turnos de trabajo de los meseros.
 *
 * El turno activo vive en el dispositivo (localStorage), no aca: el servidor no
 * guarda estado de dispositivo. Lo que si valida el servidor es que no haya dos
 * turnos abiertos para la misma persona, en cualquier dispositivo.
 */
class WorkShiftService
{
    /** Alcance del contador de intentos, para no compartirlo con el portal. */
    private const PIN_SCOPE = 'work_shift';

    public function __construct(protected PinAuthService $pinAuth) {}

    /**
     * Meseros que pueden marcar turno: tienen el permiso y un PIN configurado.
     */
    public function workers(): Collection
    {
        return User::role('waiter')
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);
    }

    /**
     * Abre un turno para un mesero, validando su PIN.
     *
     * El PIN se compara siempre con Hash::check y el mensaje de error es
     * generico a proposito: no debe revelar si un usuario existe o no.
     */
    public function open(User $operator, int $workerId, string $pin, ?string $notes = null): WorkShift
    {
        $worker = User::with('roles')->find($workerId);

        if (! $worker) {
            // Mismo mensaje que un PIN incorrecto, para no filtrar que usuarios
            // existen.
            throw new RuntimeException($this->pinAuth->invalidMessage());
        }

        // Solo los meseros marcan turno. Un PIN de otro rol no debe revelar mas
        // que un PIN equivocado, pero igual cuenta como intento.
        if (! $worker->hasRole('waiter')) {
            $this->pinAuth->registerFailedAttempt($worker->id, self::PIN_SCOPE);

            throw new RuntimeException($this->pinAuth->invalidMessage());
        }

        $this->pinAuth->verify($worker, $pin, self::PIN_SCOPE);

        // Una persona no puede tener dos turnos abiertos, en ningun dispositivo.
        $alreadyOpen = WorkShift::where('user_id', $worker->id)
            ->where('status', 'open')
            ->first();

        if ($alreadyOpen) {
            throw new RuntimeException("{$worker->name} ya tiene un turno abierto desde las ".$alreadyOpen->opened_at->format('H:i').'.');
        }

        return WorkShift::create([
            'user_id' => $worker->id,
            'opened_at' => now(),
            'status' => 'open',
            'notes' => $notes,
        ]);
    }

    /**
     * Cierra un turno. Puede hacerlo su dueno o el admin.
     */
    public function close(WorkShift $shift, User $actor, ?string $notes = null): WorkShift
    {
        if (! $shift->isOpen()) {
            throw new RuntimeException('Este turno ya está cerrado.');
        }

        $isOwner = $shift->user_id === $actor->id;

        if (! $isOwner && ! $actor->can('manage_settings')) {
            throw new RuntimeException('No tienes permiso para cerrar el turno de otra persona.');
        }

        $shift->update([
            'closed_at' => now(),
            'status' => 'closed',
            'notes' => $notes ?? $shift->notes,
        ]);

        return $shift->fresh();
    }

    /**
     * Valida un work_shift_id que manda el cliente.
     *
     * No se confia en el navegador: si el turno no existe, no esta abierto, o
     * quien llama no tiene el permiso, se devuelve null y el pedido se guarda
     * sin atribucion. Perder la atribucion es mejor que perder la comanda.
     */
    public function resolveShiftId(User $operator, mixed $shiftId): ?int
    {
        if (blank($shiftId) || ! $operator->can('clock_shift')) {
            return null;
        }

        $shift = WorkShift::where('id', $shiftId)->where('status', 'open')->first();

        return $shift?->id;
    }

    /**
     * Resumen por mesero del dia: turnos, horas, comandas, facturas y ventas.
     */
    public function dailySummary(?string $date = null): array
    {
        $date = $date ?: today()->toDateString();
        $from = now()->parse($date)->startOfDay();
        $to = $from->copy()->addDay();

        $shifts = WorkShift::with('user')
            ->whereBetween('opened_at', [$from, $to])
            ->get()
            ->groupBy('user_id');

        // LEFT JOIN a propósito: una comanda cuenta aunque todavia no se haya
        // facturado. Con JOIN interno las comandas abiertas desaparecian del
        // conteo, que es justo lo que el admin quiere ver.
        $orders = DB::table('orders')
            ->leftJoin('invoices', 'orders.id', '=', 'invoices.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('orders.work_shift_id')
            ->get([
                'orders.work_shift_id',
                'orders.id as order_id',
                'invoices.id as invoice_id',
                'invoices.total as invoice_total',
            ]);

        $byShift = $orders->groupBy('work_shift_id');
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return $users->map(function ($user) use ($shifts, $byShift) {
            $userShifts = $shifts->get($user->id, collect());
            $minutes = $userShifts->sum(fn ($s) => $s->workedMinutes());

            // $byShift esta indexado por work_shift_id, asi que se recorren los
            // turnos de la persona y se juntan sus comandas.
            $rows = $userShifts->flatMap(fn ($s) => $byShift->get($s->id, collect()));

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'turnos' => $userShifts->count(),
                'turno_abierto' => $userShifts->contains(fn ($s) => $s->status === 'open'),
                'minutos' => $minutes,
                'horas' => round($minutes / 60, 1),
                'comandas' => $rows->count(),
                'facturas' => $rows->whereNotNull('invoice_id')->count(),
                'ventas' => (float) $rows->whereNotNull('invoice_id')->sum('invoice_total'),
            ];
        })->values()->all();
    }

    private function invalidPinMessage(): string
    {
        return $this->pinAuth->invalidMessage();
    }
}
