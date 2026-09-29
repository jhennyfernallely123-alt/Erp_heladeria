<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lista de compras del día siguiente.
 *
 * El admin la arma desde Inventario. Dos reglas que se respetan en todo el
 * servicio y conviene no romper:
 *
 * - Pedir un insumo dos veces SUMA en la misma línea, no crea otra.
 * - Solo marcar como comprado suma al stock. Cancelar NO toca el stock: la
 *   cantidad nunca se había descontado, así que devolverla lo dejaba negativo.
 */
class PurchaseService
{
    /**
     * Lista de compras, primero las pendientes.
     *
     * Por defecto solo las pendientes, que es lo que hay que hacer mañana. Con
     * ?status=all se ve el historial, para consultar qué se compró y cuándo.
     */
    public function index(string $status = Purchase::STATUS_PENDING): Collection
    {
        $query = Purchase::with(['supply', 'requester', 'buyer'])->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Pasa un insumo a la lista de compras.
     *
     * Si ya hay una compra pendiente del mismo insumo, no crea otra: le suma
     * la cantidad. Mandar el mismo insumo dos veces en la tarde tiene que
     * terminar en una sola línea con la cantidad sumada, no en dos renglones
     * que el admin tiene que summed a mano.
     */
    public function request(Supply $supply, float $quantity, User $user, ?string $note = null): Purchase
    {
        if ($quantity <= 0) {
            throw new RuntimeException('La cantidad a comprar debe ser mayor a cero.');
        }

        $pending = Purchase::where('supply_id', $supply->id)
            ->where('status', Purchase::STATUS_PENDING)
            ->first();

        if ($pending) {
            // Se castea a string antes de sumar: la columna es decimal y
            // brick/math avisa si se le pasa un float crudo.
            $pending->update([
                'quantity' => round((float) $pending->quantity + $quantity, 3).'',
                'note' => $note ?? $pending->note,
            ]);

            return $pending->fresh(['supply', 'requester']);
        }

        return Purchase::create([
            'supply_id' => $supply->id,
            'supply_name' => $supply->name,
            'unit' => $supply->unit,
            // String formateado: ver SupplyService::updateQuantity.
            'quantity' => number_format($quantity, 3, '.', ''),
            'note' => $note,
            'status' => Purchase::STATUS_PENDING,
            'requested_by' => $user->id,
        ])->fresh(['supply', 'requester']);
    }

    /**
     * Marca la compra como hecha y devuelve la cantidad al stock del insumo.
     *
     * Se hace en una transacción: si la compra queda marcada pero el stock no se
     * suma, el insumo aparece con la cantidad vieja y parece que nunca se
     * compró. Y al revés: si el stock sube y la compra sigue pendiente, se
     * vuelve a sumar al próximo marcado.
     */
    public function markAsBought(Purchase $purchase, User $user, ?float $receivedQuantity = null): Purchase
    {
        if (! $purchase->isPending()) {
            throw new RuntimeException('Esta compra ya estaba marcada como hecha.');
        }

        return DB::transaction(function () use ($purchase, $user, $receivedQuantity) {
            $purchase->update([
                'status' => Purchase::STATUS_BOUGHT,
                'bought_at' => now(),
                'bought_by' => $user->id,
            ]);

            // Si el insumo sigue existiendo, se le suma lo comprado. Si fue
            // borrado del inventario mientras compraba, la compra queda
            // registrada igual pero sin a que sumarle.
            $supply = $purchase->supply()->first();

            if ($supply) {
                $amount = $receivedQuantity ?? (float) $purchase->quantity;

                // Se suma aca y no con $supply->increment(): increment() hace
                // la suma sobre el valor casteado de la columna, y eso pasa
                // un float a brick/math, que ya lo depreca.
                $newQuantity = round((float) $supply->quantity + $amount, 3);

                $supply->update(['quantity' => number_format($newQuantity, 3, '.', '')]);
            }

            return $purchase->fresh(['supply', 'buyer']);
        });
    }

    /**
     * Cancela una compra pendiente.
     *
     * NO toca el stock: la cantidad de la compra nunca se habia descontado del
     * insumo, asi que cancelar tampoco tiene que devolverla. Descontar ahi
     * dejaba el stock en negativo.
     */
    public function cancel(Purchase $purchase): void
    {
        if (! $purchase->isPending()) {
            throw new RuntimeException('Solo se pueden cancelar compras pendientes.');
        }

        $purchase->delete();
    }

    public function stats(Collection $purchases): array
    {
        $pending = $purchases->where('status', Purchase::STATUS_PENDING);

        return [
            'pending_count' => $pending->count(),
            'bought_today' => $purchases->filter(
                fn ($p) => $p->bought_at?->isToday()
            )->count(),
            'supplies' => $pending->pluck('supply_id')->filter()->unique()->count(),
        ];
    }

    public function present(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'supply_id' => $purchase->supply_id,
            'supply_name' => $purchase->supply_name,
            'unit' => $purchase->unit,
            'quantity' => (float) $purchase->quantity,
            'quantity_label' => $purchase->quantityLabel(),
            'note' => $purchase->note ?? null,
            'status' => $purchase->status,
            'requested_by_name' => $purchase->requester?->name ?? null,
            'created_at' => $purchase->created_at?->toIso8601String(),
            'bought_at' => $purchase->bought_at?->toIso8601String(),
            'bought_by_name' => $purchase->buyer?->name ?? null,
            'supply_exists' => $purchase->supply !== null,
        ];
    }

    public function presentMany(Collection $purchases): array
    {
        return $purchases->map(fn ($p) => $this->present($p))->values()->all();
    }
}
