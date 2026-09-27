<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->decimal('min_alert', 12, 2)->default(5.00);
            $table->enum('stock_type', ['unit', 'bulk_grams'])->default('unit');
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id'], 'product_stocks_product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
