<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Copia el stock del producto base a product_stocks.
        DB::table('products')->orderBy('id')->chunk(200, function ($products) {
            foreach ($products as $product) {
                DB::table('product_stocks')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'quantity' => $product->stock_quantity,
                    'min_alert' => $product->min_stock_alert,
                    'stock_type' => $product->stock_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // 2. Copia el stock de cada variante a su propia fila.
        DB::table('product_variants')->orderBy('id')->chunk(200, function ($variants) {
            foreach ($variants as $variant) {
                DB::table('product_stocks')->insert([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $variant->stock_quantity,
                    'min_alert' => 5.00,
                    'stock_type' => 'unit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // 3. products se queda solo con la identidad y el valor del producto.
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'min_stock_alert', 'stock_type']);
            $table->string('image')->nullable()->after('description');
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('stock_quantity');
        });

        // 4. Borrar un producto ya no puede destruir el historial de ventas.
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image', 'deleted_at']);
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
            $table->decimal('min_stock_alert', 12, 2)->default(5.00);
            $table->enum('stock_type', ['unit', 'bulk_grams'])->default('unit');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 2)->default(0.00);
        });

        DB::table('product_stocks')
            ->whereNull('product_variant_id')
            ->orderBy('id')
            ->get()
            ->each(function ($stock) {
                DB::table('products')->where('id', $stock->product_id)->update([
                    'stock_quantity' => $stock->quantity,
                    'min_stock_alert' => $stock->min_alert,
                    'stock_type' => $stock->stock_type,
                ]);
            });

        DB::table('product_stocks')
            ->whereNotNull('product_variant_id')
            ->orderBy('id')
            ->get()
            ->each(function ($stock) {
                DB::table('product_variants')->where('id', $stock->product_variant_id)->update([
                    'stock_quantity' => $stock->quantity,
                ]);
            });

        // Deshace la copia de up(). Las tablas product_stocks y stock_movements
        // pertenecen a las migraciones 100000 y 100001 y no se eliminan aquí:
        // hacerlas drop aquí dejaba el esquema sin poder volver a migrar.
        // Se vacían porque en este punto son un espejo de products/product_variants,
        // y conservarlas rompería la unicidad cuando up() se ejecute de nuevo
        // (el índice único no cubre product_variant_id = NULL en MySQL).
        DB::table('stock_movements')->delete();
        DB::table('product_stocks')->delete();
    }
};
