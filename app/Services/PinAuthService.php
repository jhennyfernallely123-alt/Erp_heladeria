<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Validacion de PIN para las pantallas de dispositivo compartido.
 *
 * El PIN de 4 digitos es la unica credencial que usa alguien que no se
 * loguea con email: marca su turno de mesero o entra a su portal de empleado.
 * Como la heladeria tiene un solo local y el dispositivo se cambia de persona
 * fisicamente, el bloqueo por intentos es lo que evita que alguien pruebe
 * PINs hasta abrir la ficha de otro.
 *
 * El control vive aca y no duplicado en cada modulo, para que el limite de
 * intentos sea el mismo en todos lados.
 */
class PinAuthService
{
    /** Intentos fallidos antes de bloquear el PIN de una persona. */
    public const MAX_ATTEMPTS = 5;

    /** Minutos de bloqueo tras agotar los intentos. */
    public const LOCK_MINUTES = 5;

    /**
     * Compara un PIN contra el de un usuario, aplicando el bloqueo.
     *
     * $scope distingue para que el portal de empleados y el modulo de turnos
     * no compartan el mismo contador: fallar en uno no debe agotar los
     * intentos del otro.
     *
     * El mensaje de error es generico a proposito: no debe revelar si un
     * usuario existe, ni si su PIN esta simplemente mal.
     *
     * @throws RuntimeException
     */
    public function verify(User $user, string $pin, string $scope = 'default'): void
    {
        if ($this->isLocked($user->id, $scope)) {
            throw new RuntimeException(
                'Demasiados intentos. Espera unos minutos antes de volver a intentar.'
            );
        }

        if (blank($user->pin) || ! Hash::check($pin, $user->pin)) {
            $this->registerFailedAttempt($user->id, $scope);

            throw new RuntimeException($this->invalidMessage());
        }

        $this->clearFailedAttempts($user->id, $scope);
    }

    public function isLocked(int $userId, string $scope = 'default'): bool
    {
        return (int) Cache::get($this->lockKey($userId, $scope), 0) >= self::MAX_ATTEMPTS;
    }

    /**
     * Cantidad de intentos fallidos que le quedan a una persona. Lo usa la
     * vista para avisarle antes de que quede bloqueada.
     */
    public function remainingAttempts(int $userId, string $scope = 'default'): int
    {
        return max(0, self::MAX_ATTEMPTS - (int) Cache::get($this->lockKey($userId, $scope), 0));
    }

    public function registerFailedAttempt(int $userId, string $scope = 'default'): void
    {
        $key = $this->lockKey($userId, $scope);
        $attempts = (int) Cache::get($key, 0) + 1;

        // El contador se olvida solo: el bloqueo es temporal y no necesita
        // guardarse en la base de datos.
        Cache::put($key, $attempts, now()->addMinutes(self::LOCK_MINUTES));
    }

    public function clearFailedAttempts(int $userId, string $scope = 'default'): void
    {
        Cache::forget($this->lockKey($userId, $scope));
    }

    public function invalidMessage(): string
    {
        return 'PIN incorrecto.';
    }

    private function lockKey(int $userId, string $scope): string
    {
        return "pin_attempts:{$scope}:{$userId}";
    }
}
