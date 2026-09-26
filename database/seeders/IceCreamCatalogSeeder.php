<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
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

        $prodCono = Product::firstOrCreate(
            ['name' => 'Cono Artesanal'],
            [
                'category_id' => $catHelados->id,
                'description' => 'Cono de galleta crocante con helado de la casa',
                'cost_price' => 1500,
                'sale_price' => 5000,
                'stock_quantity' => 100,
                'min_stock_alert' => 20,
                'has_variants' => true,
            ]
        );
        ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '1 Bola'], ['cost_price' => 1500, 'sale_price' => 5000, 'stock_quantity' => 100]);
        ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '2 Bolas'], ['cost_price' => 2500, 'sale_price' => 8500, 'stock_quantity' => 100]);
        ProductVariant::firstOrCreate(['product_id' => $prodCono->id, 'name' => '3 Bolas'], ['cost_price' => 3500, 'sale_price' => 11500, 'stock_quantity' => 100]);

        $prodVaso = Product::firstOrCreate(
            ['name' => 'Vaso Helado'],
            [
                'category_id' => $catHelados->id,
                'description' => 'Vaso biodegradable con bolas de helado a elección',
                'cost_price' => 1400,
                'sale_price' => 4500,
                'stock_quantity' => 80,
                'min_stock_alert' => 15,
                'has_variants' => true,
            ]
        );
        ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Pequeño (1 bola)'], ['cost_price' => 1400, 'sale_price' => 4500, 'stock_quantity' => 80]);
        ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Mediano (2 bolas)'], ['cost_price' => 2400, 'sale_price' => 8000, 'stock_quantity' => 80]);
        ProductVariant::firstOrCreate(['product_id' => $prodVaso->id, 'name' => 'Grande (3 bolas)'], ['cost_price' => 3400, 'sale_price' => 11000, 'stock_quantity' => 80]);

        // 2. Copas Especiales
        $catCopas = Category::firstOrCreate(
            ['slug' => 'copas-especiales'],
            ['name' => 'Copas Especiales', 'icon' => 'glass']
        );

        Product::firstOrCreate(
            ['name' => 'Copa Brownie Explosion'],
            [
                'category_id' => $catCopas->id,
                'description' => 'Brownie caliente, 2 bolas de helado de vainilla, salsa fudge y crema chantilly',
                'cost_price' => 4500,
                'sale_price' => 14000,
                'stock_quantity' => 25,
                'min_stock_alert' => 5,
                'has_variants' => false,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Banana Split Clásica'],
            [
                'category_id' => $catCopas->id,
                'description' => 'Banano fresco con tres bolas de helado (fresa, vainilla, chocolate), cerezas y barquillo',
                'cost_price' => 5000,
                'sale_price' => 16000,
                'stock_quantity' => 20,
                'min_stock_alert' => 5,
                'has_variants' => false,
            ]
        );

        // 3. Para Llevar / Litros
        $catLitros = Category::firstOrCreate(
            ['slug' => 'litros-y-potes'],
            ['name' => 'Potes y Litros', 'icon' => 'box']
        );

        $prodPote = Product::firstOrCreate(
            ['name' => 'Pote Familiar'],
            [
                'category_id' => $catLitros->id,
                'description' => 'Pote térmico para llevar a casa con sabores a elección',
                'cost_price' => 8000,
                'sale_price' => 22000,
                'stock_quantity' => 40,
                'min_stock_alert' => 10,
                'has_variants' => true,
            ]
        );
        ProductVariant::firstOrCreate(['product_id' => $prodPote->id, 'name' => 'Medio Litro'], ['cost_price' => 8000, 'sale_price' => 22000, 'stock_quantity' => 40]);
        ProductVariant::firstOrCreate(['product_id' => $prodPote->id, 'name' => 'Un Litro'], ['cost_price' => 14000, 'sale_price' => 38000, 'stock_quantity' => 40]);

        // 4. Bebidas y Cafetería
        $catBebidas = Category::firstOrCreate(
            ['slug' => 'bebidas-y-cafeteria'],
            ['name' => 'Bebidas & Cafetería', 'icon' => 'coffee']
        );

        Product::firstOrCreate(
            ['name' => 'Malteada Especial'],
            [
                'category_id' => $catBebidas->id,
                'description' => 'Batido cremoso de helado con leche entera, decorado con salsa y crema',
                'cost_price' => 3500,
                'sale_price' => 11000,
                'stock_quantity' => 50,
                'min_stock_alert' => 10,
                'has_variants' => false,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Café Affogato'],
            [
                'category_id' => $catBebidas->id,
                'description' => 'Shot de espresso caliente sobre una bola de helado de vainilla artesanal',
                'cost_price' => 2000,
                'sale_price' => 7000,
                'stock_quantity' => 60,
                'min_stock_alert' => 10,
                'has_variants' => false,
            ]
        );

        // 5. Toppings Adicionales
        $catToppings = Category::firstOrCreate(
            ['slug' => 'toppings-adicionales'],
            ['name' => 'Toppings Adicionales', 'icon' => 'sparkles']
        );

        Product::firstOrCreate(
            ['name' => 'Lluvia de Chocolate / Maní'],
            [
                'category_id' => $catToppings->id,
                'description' => 'Porción extra de chispas o maní crocante',
                'cost_price' => 500,
                'sale_price' => 1500,
                'stock_quantity' => 150,
                'min_stock_alert' => 30,
                'has_variants' => false,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Salsa Caliente de Arequipe'],
            [
                'category_id' => $catToppings->id,
                'description' => 'Porción de salsa caliente de arequipe tradicional',
                'cost_price' => 800,
                'sale_price' => 2000,
                'stock_quantity' => 4, // Intentionally low stock for alert test
                'min_stock_alert' => 10,
                'has_variants' => false,
            ]
        );
    }
}
