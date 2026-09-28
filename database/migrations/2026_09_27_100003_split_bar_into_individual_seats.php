<?php

use App\Models\RestaurantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * La barra del salon no es una mesa de 8 personas: son 8 sillas y cada una se
 * cobra por separado. Este paso reemplaza la mesa unica "Barra / Mostrador"
 * por 8 asientos independientes de capacidad 1, numerados del 8 al 15.
 *
 * Es una migracion y no solo un cambio en el seeder porque la fila vieja ya
 * esta en la base de datos: `db:seed` usa firstOrCreate y no la borra.
 */
return new class extends Migration
{
    /** Cantidad de sillas de la barra. */
    private const BARRA_SEATS = 8;

    /** Nombre de la mesa unica que se reemplaza. */
    private const OLD_BAR_NAME = 'Barra / Mostrador';

    /** La barra arranca despues de las 7 mesas de salon. */
    private const FIRST_BAR_NUMBER = 8;

    public function up(): void
    {
        if (! Schema::hasTable('restaurant_tables')) {
            return;
        }

        $old = RestaurantTable::where('name', self::OLD_BAR_NAME)->first();

        if ($old) {
            if ($old->orders()->exists()) {
                // Tiene comandas historicas: se conserva y se renombra para que
                // no se confunda con los asientos nuevos. Borrarla dejaria
                // ordenes huerfanos.
                $old->update(['name' => self::OLD_BAR_NAME . ' (grupo)']);
            } else {
                $old->delete();
            }
        }

        for ($seat = 1; $seat <= self::BARRA_SEATS; $seat++) {
            RestaurantTable::updateOrCreate(
                ['number' => (string) (self::FIRST_BAR_NUMBER + $seat - 1)],
                [
                    'name' => "Barra {$seat}",
                    'capacity' => 1,
                    'status' => 'available',
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('restaurant_tables')) {
            return;
        }

        foreach (range(1, self::BARRA_SEATS) as $seat) {
            $row = RestaurantTable::where('name', "Barra {$seat}")->first();

            // No se borra un asiento que ya tenga comandas.
            if ($row && ! $row->orders()->exists()) {
                $row->delete();
            }
        }

        RestaurantTable::updateOrCreate(
            ['number' => (string) self::FIRST_BAR_NUMBER],
            [
                'name' => self::OLD_BAR_NAME,
                'capacity' => 8,
                'status' => 'available',
            ]
        );
    }
};
