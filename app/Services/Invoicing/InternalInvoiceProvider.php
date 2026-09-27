<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProviderInterface;
use App\Models\Invoice;
use App\Models\BusinessSetting;

class InternalInvoiceProvider implements InvoiceProviderInterface
{
    public function __construct(protected PdfRenderer $pdf)
    {
    }

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
        $invoice->save();

        // 2. Factura A4 (Chromium) + ticket termico de 80mm (DOMPDF)
        $invoicePath = $this->pdf->renderInvoice($invoice);
        $this->pdf->renderTicket($invoice);

        $invoice->pdf_path = $invoicePath;
        $invoice->save();

        return [
            'success' => true,
            'invoice_number' => $invoice->invoice_number,
            'pdf_path' => $invoicePath,
            'ticket_path' => $this->pdf->ticketPath($invoice),
            'cufe' => null,
            'dian_status' => 'accepted',
            'message' => 'Factura interna generada exitosamente',
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
