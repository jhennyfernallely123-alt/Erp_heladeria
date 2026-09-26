<?php

namespace App\Contracts;

use App\Models\Invoice;

interface InvoiceProviderInterface
{
    /**
     * Generate or submit the invoice.
     * Returns array with: ['success' => bool, 'invoice_number' => string, 'pdf_path' => string, 'cufe' => ?string, 'dian_status' => ?string, 'message' => string]
     */
    public function generateInvoice(Invoice $invoice): array;

    /**
     * Check status of electronic invoice (if applicable).
     */
    public function checkStatus(string $invoiceId): string;

    /**
     * Cancel an issued invoice.
     */
    public function cancelInvoice(Invoice $invoice, string $reason): bool;
}
