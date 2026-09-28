<?php

use App\Http\Middleware\EmployeePortalAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Sesion propia del portal de empleados. Deliberadamente NO es
            // auth:sanctum: un PIN de 4 digitos no puede dar acceso al resto
            // de la API.
            'portal' => EmployeePortalAuthenticate::class,
        ]);

        // Esta aplicacion es solo API y no tiene ruta 'login'. Si el middleware
        // intenta redirigir a un invitado, route('login') lanza
        // RouteNotFoundException y la respuesta es un 500 en vez de un 401.
        // Devolver null hace que lance AuthenticationException, que ya sabe
        // responder 401 en JSON.
        $middleware->redirectGuestsTo(
            fn ($request) => ($request->is('api/*') ? null : '/')
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Esta aplicacion es solo API: no hay ruta 'login' a la que Sanctum
        // pueda redirigir. Sin esto, una peticion sin token y sin la cabecera
        // Accept: application/json (por ejemplo al pegar la URL en el navegador)
        // devolvia un 500 por RouteNotFoundException en vez de un 401 claro.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
