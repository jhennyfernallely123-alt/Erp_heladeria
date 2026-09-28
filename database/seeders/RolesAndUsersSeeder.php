<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /** PIN del mesero de prueba. Solo aplica la primera vez que se siembra. */
    private const DEFAULT_WAITER_PIN = '1234';

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
        if (blank($waiter->pin)) {
            $waiter->forceFill(['pin' => Hash::make(self::DEFAULT_WAITER_PIN)])->save();
        }

        $kitchen = User::firstOrCreate(
            ['email' => 'cocina@heladeria.com'],
            ['name' => 'Barra y Cocina', 'password' => Hash::make('password')]
        );
        $kitchen->syncRoles([$kitchenRole]);
    }
}
