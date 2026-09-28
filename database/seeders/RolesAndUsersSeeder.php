<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    /** PIN del mesero de prueba. Solo aplica la primera vez que se siembra. */
    private const DEFAULT_WAITER_PIN = '1234';

    /**
     * PIN de los demas usuarios de prueba en el portal de empleados.
     * Es el mismo para todos para que se pueda probar el flujo sin tener que
     * acordarse de cuatro numeros distintos.
     */
    private const DEFAULT_EMPLOYEE_PIN = '1234';

    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            'view_products', 'manage_products',
            'view_tables', 'manage_tables',
            'take_orders', 'update_orders',
            'checkout_invoice', 'manage_cash_register',
            'view_financial_reports', 'manage_settings',
            'take_deliveries',
            'clock_shift',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $waiterRole = Role::firstOrCreate(['name' => 'waiter', 'guard_name' => 'web']);
        $kitchenRole = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);

        // Assign all permissions to Admin
        $adminRole->syncPermissions(Permission::all());

        // Cashier permissions
        // El cajero es quien contesta el telefono de los domicilios, asi que
        // tambien es quien los toma.
        $cashierRole->syncPermissions([
            'view_products',
            'view_tables',
            'take_orders',
            'update_orders',
            'checkout_invoice',
            'manage_cash_register',
            'take_deliveries',
        ]);

        // Waiter permissions
        // clock_shift habilita el modulo de turnos: ver el listado de meseros
        // y abrir un turno con el PIN propio.
        $waiterRole->syncPermissions([
            'view_products',
            'view_tables',
            'take_orders',
            'update_orders',
            'clock_shift',
        ]);

        // Kitchen permissions
        $kitchenRole->syncPermissions([
            'view_tables',
            'update_orders',
        ]);

        // 3. Create Demo Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@heladeria.com'],
            ['name' => 'Administrador General', 'password' => Hash::make('password')]
        );
        $admin->syncRoles([$adminRole]);

        $cashier = User::firstOrCreate(
            ['email' => 'cajero@heladeria.com'],
            ['name' => 'Cajero Principal', 'password' => Hash::make('password')]
        );
        $cashier->syncRoles([$cashierRole]);

        $waiter = User::firstOrCreate(
            ['email' => 'mesero@heladeria.com'],
            ['name' => 'Mesero Turno 1', 'password' => Hash::make('password')]
        );
        $waiter->syncRoles([$waiterRole]);

        // El PIN se guarda con hash. El admin puede resetearlo desde el modulo
        // de turnos si el mesero lo olvida. firstOrCreate no lo sobreescribe en
        // una reejecucion, asi que solo se asigna si falta.
        //
        // Los cuatro usuarios de desarrollo tambien entran al portal de
        // empleados, que es para cualquier persona con PIN sin importar el rol.
        // Todos comparten el PIN de prueba salvo el mesero, que ya tenia el
        // suyo desde antes.
        $this->ensurePin($admin);
        $this->ensurePin($cashier);
        $this->ensurePin($waiter, self::DEFAULT_WAITER_PIN);

        $kitchen = User::firstOrCreate(
            ['email' => 'cocina@heladeria.com'],
            ['name' => 'Barra y Cocina', 'password' => Hash::make('password')]
        );
        $kitchen->syncRoles([$kitchenRole]);
        $this->ensurePin($kitchen);

        // Fechas de la ficha de los usuarios de desarrollo. hired_at es la base
        // del saldo de vacaciones: sin ella el saldo es cero y el admin ve el
        // aviso de que falta cargarla.
        $this->ensureProfile($admin, '2023-01-16', '1988-06-14', '05-24');
        $this->ensureProfile($cashier, '2024-03-04', '1995-11-02', '08-11');
        $this->ensureProfile($waiter, '2025-11-10', '2001-02-20', '09-06');
        $this->ensureProfile($kitchen, '2024-07-01', '1990-09-30', '12-18');
    }

    /**
     * Asigna el PIN de prueba si el usuario aun no tiene uno. No sobreescribe
     * un PIN real: en una reejecucion del seeder se respetaria el que ya esta.
     */
    private function ensurePin(User $user, string $pin = self::DEFAULT_EMPLOYEE_PIN): void
    {
        if (blank($user->pin)) {
            $user->forceFill(['pin' => Hash::make($pin)])->save();
        }
    }

    /** Carga la ficha solo si falta, por la misma razon que el PIN. */
    private function ensureProfile(
        User $user,
        string $hiredAt,
        string $birthDate,
        string $familyDay
    ): void {
        if (! $user->hired_at) {
            $user->forceFill([
                'hired_at' => $hiredAt,
                'birth_date' => $birthDate,
                'family_day' => date('Y').'-'.$familyDay,
            ])->save();
        }
    }
}
