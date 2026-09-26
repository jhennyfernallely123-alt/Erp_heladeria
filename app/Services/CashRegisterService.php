<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Exception;

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

    public function openRegister(User $user, float $openingBalance, ?string $notes = null): CashRegister
    {
        $existing = $this->getCurrentSession($user);
        if ($existing) {
            throw new Exception('Ya existe un turno de caja abierto para este usuario.');
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

        // Calculate expected cash balance:
        // opening_balance + cash sales + cash_in movements - cash_out movements
        $cashInMovements = (float) $register->movements()->where('type', 'cash_in')->sum('amount');
        $cashOutMovements = (float) $register->movements()->where('type', 'cash_out')->sum('amount');

        // Cash sales during this shift
        $cashSales = (float) DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.payment_method', 'cash')
            ->whereBetween('invoices.created_at', [$register->opened_at, now()])
            ->sum('payments.amount');

        $expectedBalance = (float) $register->opening_balance + $cashSales + $cashInMovements - $cashOutMovements;
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
}
