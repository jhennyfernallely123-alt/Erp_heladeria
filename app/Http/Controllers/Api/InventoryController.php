<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends BaseApiController
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = ProductStock::with(['product.category', 'variant']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        match ($request->query('status')) {
            'critical' => $query->where('quantity', '<=', 0),
            'low' => $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_alert'),
            default => null,
        };

        $stocks = $query
            ->orderByRaw('CASE WHEN quantity <= 0 THEN 0 WHEN quantity <= min_alert THEN 1 ELSE 2 END')
            ->orderBy('id')
            ->get();

        $stats = [
            'total_products' => $stocks->count(),
            'total_units' => (float) $stocks->sum(fn ($s) => (float) $s->quantity),
            'low_count' => $stocks->filter(fn ($s) => $s->status() === 'low')->count(),
            'critical_count' => $stocks->filter(fn ($s) => $s->status() === 'critical')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inventario consultado exitosamente',
            'data' => $stocks,
            'meta' => ['stats' => $stats],
        ]);
    }

    public function lowStock(): JsonResponse
    {
        return $this->successResponse($this->inventoryService->getLowStockProducts());
    }

    public function adjust(Request $request, ProductStock $stock): JsonResponse
    {
        $validated = $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'type' => 'required|in:in,out,adjustment',
            'reason' => 'required|string|max:255',
        ]);

        $this->inventoryService->adjustStock(
            $stock->product,
            $stock->variant,
            (float) $validated['new_quantity'],
            $validated['reason'],
            $request->user()?->id,
            $validated['type'],
        );

        return $this->successResponse(
            $stock->fresh(['product.category', 'variant']),
            'Stock ajustado exitosamente'
        );
    }
}
