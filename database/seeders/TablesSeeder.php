<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantTable;

class TablesSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['number' => '1', 'name' => 'Mesa 1 (Interior)', 'capacity' => 4],
            ['number' => '2', 'name' => 'Mesa 2 (Interior)', 'capacity' => 4],
            ['number' => '3', 'name' => 'Mesa 3 (Ventana)', 'capacity' => 2],
            ['number' => '4', 'name' => 'Mesa 4 (Ventana)', 'capacity' => 2],
            ['number' => '5', 'name' => 'Mesa 5 (Familiar)', 'capacity' => 6],
            ['number' => '6', 'name' => 'Mesa 6 (Terraza)', 'capacity' => 4],
            ['number' => '7', 'name' => 'Mesa 7 (Terraza)', 'capacity' => 4],
        ];

        // La barra no es una mesa de 8 personas: son 8 sillas y cada una se
        // cobra por separado, asi que son 8 mesas de capacidad 1.
        for ($seat = 1; $seat <= 8; $seat++) {
            $tables[] = [
                'number' => (string) (7 + $seat),
                'name' => "Barra {$seat}",
                'capacity' => 1,
            ];
        }

        foreach ($tables as $tbl) {
            RestaurantTable::firstOrCreate(
                ['number' => $tbl['number']],
                ['name' => $tbl['name'], 'capacity' => $tbl['capacity'], 'status' => 'available']
            );
        }
    }
}
