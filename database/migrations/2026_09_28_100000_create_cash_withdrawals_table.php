<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retiro de gaveta: dinero que el administrador saca de la gaveta para
        // pagos que no son gastos menores del turno (nomina, vacaciones,
        // recibos de la heladeria, mercancia). No pertenece a un turno, por eso
        // es tabla propia y no un cash_movement: ademas se puede hacer con la
        // gaveta cerrada.
        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('category', ['payroll', 'vacation', 'utilities', 'merchandise', 'other'])->default('other');
            $table->string('reason');
            $table->string('receipt_number')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('withdrawn_at', 6);
            $table->timestamps();

            $table->index('withdrawn_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_withdrawals');
    }
};
