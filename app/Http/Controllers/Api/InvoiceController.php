<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\PdfRenderer;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends BaseApiController
{
    public function __construct(
        protected OrderService $orderService,
        protected PdfRenderer $pdf,
    ) {
    }

    public function index(): JsonResponse
    {
        $invoices = Invoice::with(['order.table', 'payments'])->latest()->paginate(20);
        return $this->successResponse($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_doc_type' => 'nullable|string|max:10',
            'customer_doc_number' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:20',
            'tip_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|in:cash,card,transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_code' => 'nullable|string',
            'billing_mode' => 'nullable|in:internal,dian',
        ]);

        $order = Order::with('items')->findOrFail($validated['order_id']);

        if ($order->status === 'closed') {
            return $this->errorResponse('Este pedido ya fue facturado y cerrado previamente.', 422);
        }

        // Validate total payments matches order total
        $totalPayments = array_sum(array_column($validated['payments'], 'amount'));
        $subtotal = (float) $order->subtotal;
        $tip = (float) ($validated['tip_amount'] ?? $order->tip_amount ?? 0);
        $discount = (float) ($validated['discount_amount'] ?? $order->discount_total ?? 0);
        $tax = (float) ($order->tax_total ?? 0);
        $finalTotal = max(0, $subtotal - $discount + $tip + $tax);

        if (abs($totalPayments - $finalTotal) > 0.05) {
            return $this->errorResponse("La suma de los pagos (\${$totalPayments}) no coincide con el total a pagar (\${$finalTotal}).", 422);
        }

        return DB::transaction(function () use ($order, $validated, $subtotal, $tip, $discount, $tax, $finalTotal) {
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => 'TEMP-' . uniqid(),
                'customer_name' => $validated['customer_name'] ?? 'Consumidor Final',
                'customer_doc_type' => $validated['customer_doc_type'] ?? 'CC',
                'customer_doc_number' => $validated['customer_doc_number'] ?? '222222222222',
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'tip_amount' => $tip,
                'total' => $finalTotal,
                'billing_mode' => $validated['billing_mode'] ?? 'internal',
            ]);

            foreach ($validated['payments'] as $pay) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'payment_method' => $pay['payment_method'],
                    'amount' => $pay['amount'],
                    'reference_code' => $pay['reference_code'] ?? null,
                ]);
            }

            // Close order and release table
            $this->orderService->updateStatus($order, 'closed');

            // Generate invoice through provider
            $provider = InvoiceService::getProvider($validated['billing_mode'] ?? null);
            $generationResult = $provider->generateInvoice($invoice);

            return $this->successResponse([
                'invoice' => $invoice->fresh(['payments', 'order.table']),
                'generation' => $generationResult,
            ], 'Factura generada y pedido cerrado exitosamente', 201);
        });
    }

    /**
     * Factura A4. Se regenera si el archivo no esta en disco, para que un
     * documento perdido se recupere sin intervention manual.
     */
    public function downloadPdf(Invoice $invoice)
    {
        if (!$this->pdf->invoiceExists($invoice)) {
            InvoiceService::getProvider($invoice->billing_mode)->generateInvoice($invoice);
        }

        return Storage::disk('public')->download($invoice->pdf_path);
    }

    /** Ticket termico de 80mm, pensado para la impresora de rollo. */
    public function downloadTicket(Invoice $invoice)
    {
        if (!$this->pdf->ticketExists($invoice)) {
            InvoiceService::getProvider($invoice->billing_mode)->generateInvoice($invoice);
        }

        return Storage::disk('public')->download($this->pdf->ticketPath($invoice));
    }

    /**
     * Metadatos para la previsualizacion del front. El PDF se sirve aparte;
     * esto solo dice si existe, cuanto pesa y que formato tiene.
     */
    public function preview(Invoice $invoice): JsonResponse
    {
        if (!$this->pdf->invoiceExists($invoice)) {
            InvoiceService::getProvider($invoice->billing_mode)->generateInvoice($invoice);
        }

        $path = $invoice->fresh()->pdf_path;

        return $this->successResponse([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'url' => url("/api/v1/invoices/{$invoice->id}/pdf"),
            'ticket_url' => url("/api/v1/invoices/{$invoice->id}/ticket"),
            'size_bytes' => Storage::disk('public')->size($path),
            'generated_at' => optional($invoice->fresh()->updated_at)->toIso8601String(),
        ]);
    }
}
