<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordena los eventos de la gaveta con precision de microsegundo.
 *
 * El saldo de la gaveta se deriva sumando todo lo que ocurrio despues de un
 * ancla (apertura de turno o ultimo arqueo). Con datetime de un solo segundo,
 * un retiro hecho en el mismo instante en que otro cajero abre turno caia de
 * los dos lados del ancla y se restaba dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dateTime('opened_at', 6)->change();
            $table->dateTime('closed_at', 6)->nullable()->change();
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dateTime('created_at', 6)->nullable()->change();
            $table->dateTime('updated_at', 6)->nullable()->change();
        });

        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dateTime('withdrawn_at', 6)->change();
        });

        // El efectivo cobrado se cuenta por invoices.created_at, asi que tambien
        // tiene que distinguir fracciones de segundo.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('created_at', 6)->nullable()->change();
            $table->dateTime('updated_at', 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dateTime('opened_at')->change();
            $table->dateTime('closed_at')->nullable()->change();
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });

        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dateTime('withdrawn_at')->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });
    }
};
