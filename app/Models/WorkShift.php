<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Turno de trabajo de un mesero.
 *
 * No es lo mismo que `CashRegister`: esa es la gaveta de efectivo con saldo
 * base, movimientos y arqueo. Acá solo se registra entrada y salida para
 * control de asistencia y para atribuir las comandas que tomó la persona.
 */
class WorkShift extends Model
{
    use HasFactory;

    protected $table = 'work_shifts';

    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Comandas que se tomaron durante este turno. */
    public function orders()
    {
        return $this->hasMany(Order::class, 'work_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** Horas trabajadas, redondeadas a minutos. */
    public function workedMinutes(): int
    {
        $end = $this->closed_at ?? now();

        return max(0, (int) round($this->opened_at->diffInMinutes($end)));
    }
}
