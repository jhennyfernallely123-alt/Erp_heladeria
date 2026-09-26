<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProviderInterface;
use App\Models\Invoice;
use App\Models\BusinessSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InternalInvoiceProvider implements InvoiceProviderInterface
{
    public function generateInvoice(Invoice $invoice): array
    {
        // 1. Ensure consecutive invoice number if not set or empty
        if (!$invoice->invoice_number || str_starts_with($invoice->invoice_number, 'TEMP')) {
            $prefix = BusinessSetting::get('invoice_prefix', 'FAC-');
            $nextNumber = (int) BusinessSetting::get('invoice_consecutive', '1');
            $invoiceNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $invoice->invoice_number = $invoiceNumber;
            BusinessSetting::set('invoice_consecutive', (string) ($nextNumber + 1));
        }

        $invoice->billing_mode = 'internal';
        $invoice->dian_status = 'accepted';

        // 2. Compile business settings
        $settings = [
            'shop_name' => BusinessSetting::get('shop_name', 'Heladería Artesanal'),
            'shop_nit' => BusinessSetting::get('shop_nit', '900.123.456-7'),
            'shop_address' => BusinessSetting::get('shop_address', 'Calle Principal # 10 - 20'),
            'shop_phone' => BusinessSetting::get('shop_phone', '300 123 4567'),
        ];

        // 3. Generate PDF thermal ticket
        $invoice->loadMissing(['order.items.product', 'order.items.variant', 'order.table', 'payments']);

        $pdf = Pdf::loadView('pdf.ticket', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])->setPaper([0, 0, 226.77, 600], 'portrait'); // 80mm thermal width

        $fileName = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        $invoice->pdf_path = $fileName;
        $invoice->save();

        return [
            'success' => true,
            'invoice_number' => $invoice->invoice_number,
            'pdf_path' => $fileName,
            'cufe' => null,
            'dian_status' => 'accepted',
            'message' => 'Ticket de venta interno generado exitosamente',
        ];
    }

    public function checkStatus(string $invoiceId): string
    {
        return 'accepted';
    }

    public function cancelInvoice(Invoice $invoice, string $reason): bool
    {
        $invoice->dian_status = 'rejected';
        return $invoice->save();
    }
}
