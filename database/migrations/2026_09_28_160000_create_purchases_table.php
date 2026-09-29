<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de compras.
 *
 * Cuando al admin se le acaba algo al cierre, lo pasa para acá con un botón.
 * Queda como "pendiente" hasta que lo marca como comprado, y ahí se puede
 *_volver a sumar la cantidad al stock del insumo para que mañana figure lo que
 * realmente hay.
 *
 * El nombre y la unidad se copian como texto, no se leen del insumo: si
 * mañana renombran el insumo, la compra vieja tiene que seguir diciendo como
 * estaba escrita cuando se pidió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supply_name');
            $table->string('unit', 8)->default('units');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'bought'])->default('pending');

            // Quien lo pidió y quien lo compró, para saber a quién preguntarle.
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('bought_at')->nullable();
            $table->foreignId('bought_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // La vista pide primero las pendientes y ordenadas por fecha.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
