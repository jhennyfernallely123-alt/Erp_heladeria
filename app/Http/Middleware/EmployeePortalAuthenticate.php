<?php

namespace App\Http\Middleware;

use App\Services\EmployeePortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puerta del portal de empleado.
 *
 * Identifica a la persona con la sesion opaca que se abrio al validar su PIN,
 * NO con el token de Sanctum. Son dos cosas separadas a proposito: el token
 * de Sanctum es un login con email y contraseña y da acceso a todo lo que el
 * rol permita, mientras que el PIN de 4 digitos solo debe dar acceso a la
 * ficha del propio empleado.
 *
 * Si se mezclaran, un PIN adivinado abriria el sistema entero.
 */
class EmployeePortalAuthenticate
{
    public function __construct(protected EmployeePortalSession $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->readToken($request);

        $employee = $this->sessions->resolve($token);

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu sesión expiró. Volvé a ingresar con tu PIN.',
                'data' => null,
            ], 401);
        }

        // El controller lee de aqui el empleado. request()->user() sigue siendo
        // el del token de Sanctum a proposito: son identidades distintas.
        $request->attributes->set('portal_employee', $employee);

        return $next($request);
    }

    private function readToken(Request $request): ?string
    {
        $header = $request->header('X-Portal-Token');

        if (filled($header)) {
            return $header;
        }

        return $request->bearerToken();
    }
}
