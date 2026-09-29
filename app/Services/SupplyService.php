<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class SupplyService
{
    /**
     * Lista de insumos para el conteo del cierre.
     *
     * Los inactivos van al final y con opacidad en la vista: el admin los sigue
     * viendo para saber que existieron, pero no los cuenta todos los días.
     */
    public function index(?string $search = null, bool $onlyActive = false): Collection
    {
        $query = Supply::with('counter')->orderBy('is_active', 'desc')->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Cambia la cantidad de un insumo.
     *
     * REEMPLAZA el valor, no lo suma: el admin está diciendo "quedan 12 litros",
     * no "sumá 12 litros". Por eso no necesita tipo de movimiento ni motivo.
     */
    public function updateQuantity(Supply $supply, float $quantity, User $user, ?string $note = null): Supply
    {
        if ($quantity < 0) {
            throw new RuntimeException('La cantidad no puede ser negativa.');
        }

        $supply->update([
            // String formateado a proposito: la columna es decimal(12,3) y
            // brick/math avisa si se le pasa un float crudo.
            'quantity' => number_format($quantity, 3, '.', ''),
            'notes' => $note ?? $supply->notes,
            'counted_by' => $user->id,
            'counted_at' => now(),
        ]);

        return $supply->fresh('counter');
    }

    public function create(array $data, User $user): Supply
    {
        $name = trim($data['name']);

        if (Supply::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw new RuntimeException("Ya existe un insumo llamado \"{$name}\".");
        }

        $quantity = (float) ($data['quantity'] ?? 0);

        return Supply::create([
            'name' => $name,
            'unit' => $data['unit'] ?? 'units',
            'quantity' => number_format($quantity, 3, '.', ''),
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'counted_by' => $user->id,
            'counted_at' => now(),
        ])->fresh('counter');
    }

    public function update(Supply $supply, array $data): Supply
    {
        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            $exists = Supply::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $supply->id)
                ->exists();

            if ($exists) {
                throw new RuntimeException("Ya existe otro insumo llamado \"{$name}\".");
            }

            $data['name'] = $name;
        }

        $supply->update($data);

        return $supply->fresh('counter');
    }

    /**
     * Elimina el insumo. Se borra de verdad y no se desactiva: si el admin lo
     * saco de la lista es porque ya no se usa en la heladería.
     */
    public function delete(Supply $supply): void
    {
        $supply->delete();
    }

    /** Resumen para las tarjetas de arriba. */
    public function stats(Collection $supplies): array
    {
        $active = $supplies->where('is_active', true);

        return [
            'total_supplies' => $active->count(),
            'empty_count' => $active->filter(fn ($s) => (float) $s->quantity <= 0)->count(),
            'total_items' => $supplies->count(),
            // Solo los que estan en la lista de compras: por eso se consulta
            // aparte en vez de mirar los $supplies.
            'pending_purchases' => Purchase::where('status', Purchase::STATUS_PENDING)->count(),
        ];
    }

    public function present(Supply $supply): array
    {
        return [
            'id' => $supply->id,
            'name' => $supply->name,
            'unit' => $supply->unit,
            'unit_label' => $supply->unitLabel(),
            'quantity' => (float) $supply->quantity,
            'quantity_label' => $supply->quantityLabel(),
            'notes' => $supply->notes ?? null,
            'is_active' => $supply->is_active,
            'counted_at' => $supply->counted_at?->toIso8601String(),
            'counted_today' => (bool) $supply->counted_at?->isToday(),
            'counted_by_name' => $supply->counter?->name ?? null,
        ];
    }

    public function presentMany(Collection $supplies): array
    {
        return $supplies->map(fn ($s) => $this->present($s))->values()->all();
    }
}
