<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class IceCreamCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Helados Tradicionales
        $catHelados = Category::firstOrCreate(
            ['slug' => 'helados-tradicionales'],
            ['name' => 'Helados Tradicionales', 'icon' => 'ice-cream']
        );

        $prodCono = $this->makeProduct('Cono Artesanal', $catHelados, [
            'description' => 'Cono de galleta crocante con helado de la casa',
            'cost_price' => 1500,
            'sale_price' => 5000,
            'has_variants' => true,
        ], 100, 20);

        $this->makeVariant($prodCono, '1 Bola', 1500, 5000, 100);
        $this->makeVariant($prodCono, '2 Bolas', 2500, 8500, 100);
        $this->makeVariant($prodCono, '3 Bolas', 3500, 11500, 100);

        $prodVaso = $this->makeProduct('Vaso Helado', $catHelados, [
            'description' => 'Vaso biodegradable con bolas de helado a elección',
            'cost_price' => 1400,
            'sale_price' => 4500,
            'has_variants' => true,
        ], 80, 15);

        $this->makeVariant($prodVaso, 'Pequeño (1 bola)', 1400, 4500, 80);
        $this->makeVariant($prodVaso, 'Mediano (2 bolas)', 2400, 8000, 80);
        $this->makeVariant($prodVaso, 'Grande (3 bolas)', 3400, 11000, 80);

        // 2. Copas Especiales
        $catCopas = Category::firstOrCreate(
            ['slug' => 'copas-especiales'],
            ['name' => 'Copas Especiales', 'icon' => 'glass']
        );

        $this->makeProduct('Copa Brownie Explosion', $catCopas, [
            'description' => 'Brownie caliente, 2 bolas de helado de vainilla, salsa fudge y crema chantilly',
            'cost_price' => 4500,
            'sale_price' => 14000,
            'has_variants' => false,
        ], 25);

        $this->makeProduct('Banana Split Clásica', $catCopas, [
            'description' => 'Banano fresco con tres bolas de helado (fresa, vainilla, chocolate), cerezas y barquillo',
            'cost_price' => 5000,
            'sale_price' => 16000,
            'has_variants' => false,
        ], 20);

        // 3. Para Llevar / Litros
        $catLitros = Category::firstOrCreate(
            ['slug' => 'litros-y-potes'],
            ['name' => 'Potes y Litros', 'icon' => 'box']
        );

        $prodPote = $this->makeProduct('Pote Familiar', $catLitros, [
            'description' => 'Pote térmico para llevar a casa con sabores a elección',
            'cost_price' => 8000,
            'sale_price' => 22000,
            'has_variants' => true,
        ], 40, 10, 'bulk_grams');

        $this->makeVariant($prodPote, 'Medio Litro', 8000, 22000, 40);
        $this->makeVariant($prodPote, 'Un Litro', 14000, 38000, 40);

        // 4. Bebidas y Cafetería
        $catBebidas = Category::firstOrCreate(
            ['slug' => 'bebidas-y-cafeteria'],
            ['name' => 'Bebidas & Cafetería', 'icon' => 'coffee']
        );

        $this->makeProduct('Malteada Especial', $catBebidas, [
            'description' => 'Batido cremoso de helado con leche entera, decorado con salsa y crema',
            'cost_price' => 3500,
            'sale_price' => 11000,
            'has_variants' => false,
        ], 50, 10);

        $this->makeProduct('Café Affogato', $catBebidas, [
            'description' => 'Shot de espresso caliente sobre una bola de helado de vainilla artesanal',
            'cost_price' => 2000,
            'sale_price' => 7000,
            'has_variants' => false,
        ], 60, 10);

        // 5. Toppings Adicionales
        $catToppings = Category::firstOrCreate(
            ['slug' => 'toppings-adicionales'],
            ['name' => 'Toppings Adicionales', 'icon' => 'sparkles']
        );

        $this->makeProduct('Lluvia de Chocolate / Maní', $catToppings, [
            'description' => 'Porción extra de chispas o maní crocante',
            'cost_price' => 500,
            'sale_price' => 1500,
            'has_variants' => false,
        ], 150, 30);

        // 4 unidades con mínimo 10: queda en estado "Bajo" a propósito, para que
        // la pantalla de Inventario tenga una fila que destacar. Para un estado
        // "Crítico" hace falta cantidad 0, que en un catálogo real signifies
        // producto agotado y no una alerta de reposición.
        $this->makeProduct('Salsa Caliente de Arequipe', $catToppings, [
            'description' => 'Porción de salsa caliente de arequipe tradicional',
            'cost_price' => 800,
            'sale_price' => 2000,
            'has_variants' => false,
        ], 4, 10);
    }

    private function makeProduct(
        string $name,
        Category $category,
        array $attributes,
        int $quantity,
        int $minAlert = 5,
        string $stockType = 'unit'
    ): Product {
        $product = Product::firstOrCreate(
            ['name' => $name],
            array_merge(['category_id' => $category->id], $attributes)
        );

        ProductStock::resolve($product)->update([
            'quantity' => $quantity,
            'min_alert' => $minAlert,
            'stock_type' => $stockType,
        ]);

        return $product;
    }

    private function makeVariant(
        Product $product,
        string $name,
        int $costPrice,
        int $salePrice,
        int $quantity
    ): ProductVariant {
        $variant = ProductVariant::firstOrCreate(
            ['product_id' => $product->id, 'name' => $name],
            ['cost_price' => $costPrice, 'sale_price' => $salePrice]
        );

        ProductStock::resolve($product, $variant)->update([
            'quantity' => $quantity,
            'min_alert' => 5,
            'stock_type' => 'unit',
        ]);

        return $variant;
    }
}
