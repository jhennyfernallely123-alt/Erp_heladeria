<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\EmployeeService;
use App\Services\PinAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gestion de meseros. Todo es exclusivo del administrador: el cajero y la
 * cocina no entran, y el mesero tampoco.
 *
 * La ficha completa de cada empleado (contratación, cumpleaños, día de familia)
 * vive en TeamController, que es el módulo del portal de empleados.
 */
class UserController extends BaseApiController
{
    public function __construct(protected PinAuthService $pinAuth) {}

    /** Listado de usuarios con su rol y si ya tienen PIN. */
    public function index(): JsonResponse
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => $this->present($u));

        return $this->successResponse($users);
    }

    /**
     * Alta de mesero. El email se arma solo a partir del nombre para que el
     * administrador no tenga que inventar una direccion valida.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'pin' => 'required|string|size:4|digits:4',
            'phone' => 'nullable|string|max:30',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $this->buildEmail($validated['name']),
                'password' => Hash::make(Str::random(32)),
                'phone' => $validated['phone'] ?? null,
            ]);

            // El PIN se guarda con hash y nunca sale por la API.
            $user->forceFill(['pin' => Hash::make($validated['pin'])])->save();
            $user->syncRoles(['waiter']);

            return $user;
        });

        return $this->successResponse($this->present($user->fresh('roles')), 'Mesero creado', 201);
    }

    /**
     * Reseteo del PIN. Es el escape cuando alguien se lo olvida.
     *
     * Libera el bloqueo en los dos lugares donde se usa un PIN: el modulo de
     * turnos y el portal de empleados. No tiene sentido dejar a alguien
     * esperando despues de arreglar su PIN.
     */
    public function resetPin(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:4|digits:4',
        ]);

        $user->forceFill(['pin' => Hash::make($validated['pin'])])->save();

        $this->pinAuth->clearFailedAttempts($user->id, EmployeeService::PIN_SCOPE);
        $this->pinAuth->clearFailedAttempts($user->id, 'work_shift');

        return $this->successResponse($this->present($user), 'PIN actualizado');
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($validated);

        return $this->successResponse($this->present($user->fresh('roles')), 'Mesero actualizado');
    }

    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'roles' => $user->getRoleNames(),
            'has_pin' => filled($user->pin),
        ];
    }

    /** Arma un email unico a partir del nombre: juan.perez@heladeria.com. */
    private function buildEmail(string $name): string
    {
        $base = Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z]+/', '.')
            ->trim('.')
            ->limit(40, '')
            ->toString();

        if ($base === '') {
            $base = 'mesero';
        }

        $email = "{$base}@heladeria.com";
        $suffix = 2;

        while (User::where('email', $email)->exists()) {
            $email = "{$base}{$suffix}@heladeria.com";
            $suffix++;
        }

        return $email;
    }
}
