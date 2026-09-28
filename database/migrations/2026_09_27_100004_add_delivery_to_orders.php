<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el tipo de pedido `delivery` (domicilio) con los datos de entrega y
 * la tarifa de envío congelada en el pedido.
 *
 * Los datos van en `orders` y no en `invoices` porque la dirección se necesita
 * antes de facturar: el domicillero tiene que saber a dónde llevar en el
 * momento en que se toma la comanda, no cuando se cobra.
 *
 * Ver la especificación: docs/superpowers/specs/2026-09-27-domicilio-design.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // En MySQL un ENUM se cambia con ALTER. La forma portable de agregar un
        // valor es redefinir el tipo con el valor nuevo incluido.
        DB::statement("ALTER TABLE `orders` MODIFY `type` ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in'");

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_name')->nullable()->after('notes');
            $table->string('delivery_phone')->nullable()->after('delivery_name');
            $table->string('delivery_address')->nullable()->after('delivery_phone');
            $table->text('delivery_notes')->nullable()->after('delivery_address');
            $table->decimal('delivery_fee', 12, 2)->default(0.00)->after('delivery_notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_name',
                'delivery_phone',
                'delivery_address',
                'delivery_notes',
                'delivery_fee',
            ]);
        });

        // MySQL no puede reducir un ENUM que tiene filas usando el valor que
        // se quiere quitar, asi que los pedidos a domicilio pasan a 'takeaway'
        // antes de achicar el enum. Deshacer la migracion es destructivo con
        // esa informacion: por eso esta conversion es explicita y no silenciosa.
        DB::table('orders')->where('type', 'delivery')->update(['type' => 'takeaway']);

        DB::statement("ALTER TABLE `orders` MODIFY `type` ENUM('dine_in','takeaway') NOT NULL DEFAULT 'dine_in'");
    }
};
