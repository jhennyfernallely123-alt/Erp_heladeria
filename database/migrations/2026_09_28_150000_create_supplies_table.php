<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Insumos de la heladeria.
 *
 * Es una lista propia, NO el catalogo de productos. Los productos son lo que se
 * vende y el POS los usa; los insumos son lo que se compra y se consume
 * (leche, azucar, crema, fresa, vasos, tapas). No tienen relacion con
 * product_stocks, que queda sin usar.
 *
 * La cantidad NO se descuenta sola: el admin cuenta lo que realmente queda al
 * cierre de cada dia y lo carga aca. Es un conteo, no un sistema de stock
 * teorico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('unit', ['kg', 'g', 'L', 'ml', 'units'])->default('units');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            // Quien hizo el ultimo conteo y cuando: el admin revisa a diario y
            // hay que saber si una cantidad es de hoy o de hace una semana.
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('counted_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
