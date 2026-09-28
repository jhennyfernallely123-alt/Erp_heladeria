<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIN de 4 digitos por mesero, y atribucion de cada comanda a su turno.
 *
 * El PIN se guarda con hash bcrypt y nunca sale por la API. Es nullable porque
 * el admin, el cajero y la cocina no entran por esta puerta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin')->nullable()->after('password');
            $table->string('phone', 30)->nullable()->after('email');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('work_shift_id')
                ->nullable()
                ->after('user_id')
                ->constrained('work_shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'work_shift_id')) {
                $table->dropForeign(['work_shift_id']);
                $table->dropColumn('work_shift_id');
            }
        });

        // Se comprueba cada columna porque una migracion editada puede dejar
        // el esquema a medio camino y el down() no debe reventar por eso.
        $columns = array_values(array_filter(
            ['pin', 'phone'],
            fn ($c) => Schema::hasColumn('users', $c)
        ));

        if ($columns) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
