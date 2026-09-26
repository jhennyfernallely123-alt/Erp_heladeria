<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends BaseApiController
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['table', 'user', 'items.product', 'items.variant', 'invoice']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('active_only', true)) {
            $query->whereIn('status', ['open', 'in_kitchen', 'delivered']);
        }

        $orders = $query->latest()->get();
        return $this->successResponse($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'type' => 'in:dine_in,takeaway',
            'notes' => 'nullable|string',
            'tip_amount' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.notes' => 'nullable|string',
        ]);

        $order = $this->orderService->createOrder($validated, $request->user());
        return $this->successResponse($order, 'Pedido creado exitosamente', 201);
    }

    public function show(Order $order): JsonResponse
    {
        return $this->successResponse($order->load(['table', 'user', 'items.product', 'items.variant', 'invoice.payments']));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        if (in_array($order->status, ['closed', 'cancelled'])) {
            return $this->errorResponse('No se puede modificar un pedido que ya está cerrado o cancelado.', 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'tip_amount' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.notes' => 'nullable|string',
        ]);

        if (isset($validated['notes'])) $order->notes = $validated['notes'];
        if (isset($validated['tip_amount'])) $order->tip_amount = $validated['tip_amount'];
        if (isset($validated['discount_total'])) $order->discount_total = $validated['discount_total'];
        $order->save();

        $updatedOrder = $this->orderService->syncItems($order, $validated['items']);

        return $this->successResponse($updatedOrder, 'Pedido actualizado exitosamente');
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_kitchen,delivered,closed,cancelled',
        ]);

        $order = $this->orderService->updateStatus($order, $validated['status']);
        return $this->successResponse($order, 'Estado del pedido actualizado a: ' . $order->status);
    }
}
