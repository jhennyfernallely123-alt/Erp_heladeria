<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Sesion del portal de empleado.
 *
 * El portal NO usa token de Sanctum, y esa es la decision de seguridad mas
 * importante de este modulo. Un PIN de 4 digitos no puede convertirse en un
 * token completo: si lo hiciera, quien conozca el PIN del admin entraria con
 * todos sus permisos al sistema entero, incluyendo configuracion y caja.
 *
 * Entonces el PIN abre una sesion aparte y corta: un token opaco guardado en
 * cache que solo habilita las rutas del portal y que expira solo. Nunca sale
 * por la API y nunca se puede escalar a otro permiso.
 */
class EmployeePortalSession
{
    /** Minutos que dura la sesion del portal. */
    public const TTL_MINUTES = 120;

    private const PREFIX = 'employee_portal:session:';

    /**
     * Abre sesion para un empleado ya validado por su PIN.
     */
    public function issue(User $employee): string
    {
        $token = bin2hex(random_bytes(32));

        Cache::put(
            $this->key($token),
            ['user_id' => $employee->id, 'issued_at' => now()->toIso8601String()],
            now()->addMinutes(self::TTL_MINUTES)
        );

        return $token;
    }

    /**
     * Resuelve el token a su empleado. Devuelve null si no existe o ya expiro:
     * el cache expira solo, asi que no hace falta limpiar nada a mano.
     */
    public function resolve(?string $token): ?User
    {
        if (blank($token)) {
            return null;
        }

        $data = Cache::get($this->key($token));

        if (! is_array($data) || ! isset($data['user_id'])) {
            return null;
        }

        return User::find($data['user_id']);
    }

    public function forget(?string $token): void
    {
        if (filled($token)) {
            Cache::forget($this->key($token));
        }
    }

    /** Minutos que le quedan a la sesion, para avisar en la vista. */
    public function minutesLeft(?string $token): int
    {
        if (blank($token)) {
            return 0;
        }

        $data = Cache::get($this->key($token));

        if (! is_array($data) || blank($data['issued_at'] ?? null)) {
            return 0;
        }

        $expires = Carbon::parse($data['issued_at'])->addMinutes(self::TTL_MINUTES);

        return max(0, (int) now()->diffInMinutes($expires, false));
    }

    private function key(string $token): string
    {
        return self::PREFIX.hash('sha256', $token);
    }
}
