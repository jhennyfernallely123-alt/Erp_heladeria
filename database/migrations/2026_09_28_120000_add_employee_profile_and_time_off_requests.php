<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha de empleado y solicitudes de tiempo libre.
 *
 * hired_at es la base del saldo de vacaciones: los dias se acumulan solos a
 * 1,25 por mes completo trabajado desde esa fecha, asi que la fecha tiene que
 * existir y ser real. birth_date y family_day son opcionales: no todos los
 * empleados tienen o quieren compartir esos datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Base del saldo de vacaciones. Sin esta fecha el saldo es cero.
            $table->date('hired_at')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('hired_at');
            // Dia de familia: importan el mes y el dia, no el anio.
            $table->date('family_day')->nullable()->after('birth_date');
        });

        // Solicitud de vacaciones o permiso del empleado. El saldo se descuenta
        // al aprobar, no al solicitar: si se descontara al pedir, un rechazo
        // dejaria el saldo descuadrado.
        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['vacation', 'family_day', 'birthday', 'unpaid'])->default('vacation');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hired_at', 'birth_date', 'family_day']);
        });
    }
};
