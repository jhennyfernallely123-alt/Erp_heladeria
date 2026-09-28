<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashWithdrawal;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function getCurrentSession(?User $user = null): ?CashRegister
    {
        $query = CashRegister::where('status', 'open');
        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->with('movements')->latest('opened_at')->first();
    }

    /**
     * El turno abierto de la gaveta, sea de quien sea.
     *
     * La heladeria tiene una sola gaveta fisica: el dinero no es del cajero,
     * es del local. Por eso el admin puede registrar entradas y salidas sobre
     * el turno que tiene abierto el cajero, y por eso el saldo se deriva de
     * aqui en vez de del usuario.
     */
    public function getOpenSession(): ?CashRegister
    {
        return CashRegister::where('status', 'open')
            ->with('movements.user')
            ->latest('opened_at')
            ->first();
    }

    public function openRegister(User $user, float $openingBalance, ?string $notes = null): CashRegister
    {
        $existing = $this->getCurrentSession($user);
        if ($existing) {
            throw new Exception('Ya existe un turno de caja abierto para este usuario.');
        }

        // La gaveta es una sola. Si otro cajero ya la tiene abierta, el efectivo
        // esta contado dentro de su turno: abrir un segundo turno duplicaria
        // ese dinero en el saldo. El relevo se hace cerrando y abriendo.
        $openByOther = $this->getOpenSession();
        if ($openByOther) {
            throw new Exception(
                "La gaveta ya está abierta por {$openByOther->user?->name}. ".
                    'Cerrá ese turno con su arqueo antes de abrir el tuyo.'
            );
        }

        return CashRegister::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_balance' => $openingBalance,
            'status' => 'open',
            'notes' => $notes,
        ]);
    }

    public function addMovement(CashRegister $register, User $user, string $type, float $amount, string $category, string $description): CashMovement
    {
        if ($register->status !== 'open') {
            throw new Exception('No se pueden registrar movimientos en una caja cerrada.');
        }

        return CashMovement::create([
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    public function closeRegister(CashRegister $register, float $actualBalance, ?string $notes = null): CashRegister
    {
        if ($register->status !== 'open') {
            throw new Exception('Esta caja ya se encuentra cerrada.');
        }

        // El arqueo espera el saldo teorico de la gaveta, no el del cajero.
        $expectedBalance = $this->computeBalance($register->opened_at, (float) $register->opening_balance);
        $difference = $actualBalance - $expectedBalance;

        $register->update([
            'closed_at' => now(),
            'expected_balance' => $expectedBalance,
            'actual_balance' => $actualBalance,
            'difference' => $difference,
            'status' => 'closed',
            'notes' => $notes ?? $register->notes,
        ]);

        return $register->fresh(['movements']);
    }

    /**
     * Saldo teorico de la gaveta a partir de una base y una fecha.
     *
     * Base + efectivo cobrado + entradas - salidas - retiros. Es la unica
     * formula del proyecto para el saldo de la gaveta, y se reutiliza en el
     * arqueo, en el resumen del admin y en el saldo en vivo.
     *
     * Dos detalles hacen que el saldo sea exacto:
     *
     * 1. El ancla se pasa como string con microsegundos, no como Carbon. Al
     *   .bindear una fecha, el query builder usa el formato de la conexion
     *    ('Y-m-d H:i:s') y trunca a segundos. Con microsegundos en la columna
     *    pero segundos en el WHERE, todo movimiento del mismo segundo que el
     *    ancla pasaba el '>' y se contaba dos veces.
     *
     * 2. La comparacion es estricta. La base YA es el conteo fisico hecho en
     *    ese instante, asi que un evento con la misma marca ya esta contado.
     */
    private function computeBalance(Carbon $since, float $base): float
    {
        $anchor = $since->format('Y-m-d H:i:s.u');

        $cashSales = (float) DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.payment_method', 'cash')
            ->where('invoices.created_at', '>', $anchor)
            ->sum('payments.amount');

        $cashIn = (float) CashMovement::where('type', 'cash_in')->where('created_at', '>', $anchor)->sum('amount');
        $cashOut = (float) CashMovement::where('type', 'cash_out')->where('created_at', '>', $anchor)->sum('amount');
        $withdrawals = (float) CashWithdrawal::where('withdrawn_at', '>', $anchor)->sum('amount');

        return $base + $cashSales + $cashIn - $cashOut - $withdrawals;
    }

    /**
     * Saldo en vivo de la gaveta, con la gaveta abierta o cerrada.
     *
     * Si hay turno abierto, la base es su saldo de apertura. Si esta cerrada,
     * la base es el conteo fisico del ultimo turno cerrado: ese numero es la
     * verdad porque alguien lo conto a mano. Asi el efectivo no se pierde entre
     * turnos aunque pase el tiempo con la caja cerrada.
     */
    public function getDrawerBalance(): array
    {
        $open = $this->getOpenSession();

        if ($open) {
            $base = (float) $open->opening_balance;
            $since = $open->opened_at;
        } else {
            $lastClosed = CashRegister::where('status', 'closed')->latest('closed_at')->first();
            $base = $lastClosed ? (float) $lastClosed->actual_balance : 0.0;
            $since = $lastClosed ? $lastClosed->closed_at : now();
        }

        $balance = $this->computeBalance($since, $base);

        return [
            'balance' => round($balance, 2),
            'is_open' => (bool) $open,
            'anchor' => $since instanceof \DateTimeInterface ? Carbon::parse($since)->toIso8601String() : $since,
            'session' => $open ? [
                'id' => $open->id,
                'user_name' => $open->user?->name,
                'opened_at' => Carbon::parse($open->opened_at)->toIso8601String(),
                'opening_balance' => (float) $open->opening_balance,
            ] : null,
        ];
    }

    /**
     * Resumen de caja para el administrador: saldo de la gaveta, movimientos
     * del dia e ingresos en efectivo del dia.
     */
    public function getOverview(): array
    {
        $drawer = $this->getDrawerBalance();
        $dayStart = now()->startOfDay();

        $open = $this->getOpenSession();

        $cashSalesToday = (float) DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.payment_method', 'cash')
            ->where('invoices.created_at', '>=', $dayStart)
            ->sum('payments.amount');

        $cardSalesToday = (float) DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->whereIn('payments.payment_method', ['card', 'transfer', 'nequi', 'daviplata'])
            ->where('invoices.created_at', '>=', $dayStart)
            ->sum('payments.amount');

        $cashInToday = (float) CashMovement::where('type', 'cash_in')->where('created_at', '>=', $dayStart)->sum('amount');
        $cashOutToday = (float) CashMovement::where('type', 'cash_out')->where('created_at', '>=', $dayStart)->sum('amount');
        $withdrawalsToday = (float) CashWithdrawal::where('withdrawn_at', '>=', $dayStart)->sum('amount');

        $movements = CashMovement::with('user')
            ->where('created_at', '>=', $dayStart)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $withdrawals = CashWithdrawal::with('user')
            ->where('withdrawn_at', '>=', $dayStart)
            ->orderByDesc('withdrawn_at')
            ->limit(20)
            ->get();

        return [
            'drawer' => $drawer,
            'today' => [
                'cash_sales' => round($cashSalesToday, 2),
                'card_sales' => round($cardSalesToday, 2),
                'cash_in' => round($cashInToday, 2),
                'cash_out' => round($cashOutToday, 2),
                'withdrawals' => round($withdrawalsToday, 2),
            ],
            'movements' => $movements,
            'withdrawals' => $withdrawals,
            'open_session' => $open ? [
                'id' => $open->id,
                'user_name' => $open->user?->name,
                'opened_at' => Carbon::parse($open->opened_at)->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Historial de arqueos, del mas reciente al mas antiguo.
     */
    public function getHistory(int $limit = 30): array
    {
        return CashRegister::with('user')
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($register) => [
                'id' => $register->id,
                'user_name' => $register->user?->name,
                'opened_at' => Carbon::parse($register->opened_at)->toIso8601String(),
                'closed_at' => Carbon::parse($register->closed_at)->toIso8601String(),
                'opening_balance' => (float) $register->opening_balance,
                'expected_balance' => (float) $register->expected_balance,
                'actual_balance' => (float) $register->actual_balance,
                'difference' => (float) $register->difference,
            ])
            ->all();
    }

    /**
     * Retiro de gaveta: dinero que el administrador saca para pagos que no son
     * gastos menores del turno (nomina, vacaciones, recibos, mercancia).
     *
     * A diferencia de addMovement, no exige turno abierto: la gaveta fisica
     * existe aunque este cerrada, y el retiro es un hecho del local, no del
     * turno.
     */
    public function withdraw(User $user, float $amount, string $category, string $reason, ?string $receiptNumber = null, ?string $notes = null): CashWithdrawal
    {
        if ($amount <= 0) {
            throw new Exception('El monto del retiro debe ser mayor a cero.');
        }

        $balance = $this->getDrawerBalance()['balance'];
        if ($amount > $balance) {
            throw new Exception("No hay saldo suficiente en la gaveta. Saldo actual: \${$balance}.");
        }

        return CashWithdrawal::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'category' => $category,
            'reason' => $reason,
            'receipt_number' => $receiptNumber,
            'notes' => $notes,
            'withdrawn_at' => now(),
        ]);
    }
}
