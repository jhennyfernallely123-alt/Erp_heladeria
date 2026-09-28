<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // El PIN es un hash de bcrypt. Nunca debe salir por la API: ni en
        // claro ni el hash.
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Sin estos casts las fechas salen como string y el calculo de
            // vacaciones revienta al pedir un Carbon.
            'hired_at' => 'date',
            'birth_date' => 'date',
            'family_day' => 'date',
        ];
    }

    /** El usuario puede marcar turno en el modulo de turnos. */
    public function canClockShift(): bool
    {
        return $this->can('clock_shift') && filled($this->pin);
    }

    public function workShifts()
    {
        return $this->hasMany(WorkShift::class);
    }

    public function activeWorkShift()
    {
        return $this->hasOne(WorkShift::class)->where('status', 'open')->latestOfMany();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }
}
