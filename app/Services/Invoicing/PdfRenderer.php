<?php

namespace App\Services\Invoicing;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Genera los dos documentos de una venta con spatie/laravel-pdf:
 *
 *  - La factura A4 usa el driver por defecto (Browsershot), que renderiza con
 *    Chromium. Por eso la plantilla puede usar flexbox y grid, que DomPDF no
 *    soporta, y la paleta Dulce Helado se aplica sin trucos.
 *  - El ticket termico de 80mm se queda en DOMPDF: para un rollo de 57mm no
 *    tiene sentido arrancar un navegador, y DOMPDF no necesita Node ni Chrome.
 *
 * Los dos pasan por el mismo facade, asi que cambiar de motor es una variable
 * de entorno y no un cambio de codigo.
 */
class PdfRenderer
{
    /** Ancho y alto de la factura en pulgadas (A4 vertical con margen). */
    private const INVOICE_FORMAT = 'a4';

    /** Rollo termico de 80mm: ancho real util mas el area del marge. */
    private const TICKET_WIDTH_MM = 57;

    private const TICKET_HEIGHT_MM = 200;

    public function businessSettings(): array
    {
        return [
            'shop_name' => BusinessSetting::get('shop_name', 'Dulce Helado'),
            'shop_nit' => BusinessSetting::get('shop_nit', '900.123.456-7'),
            'shop_address' => BusinessSetting::get('shop_address', 'Calle Principal # 10 - 20'),
            'shop_phone' => BusinessSetting::get('shop_phone', '300 123 4567'),
        ];
    }

    /**
     * Genera la factura A4 y la guarda en el disco public.
     * Devuelve la ruta relativa dentro del disco.
     */
    public function renderInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'order.items.product',
            'order.items.variant',
            'order.table',
            'payments',
        ]);

        $fileName = 'invoices/' . $invoice->invoice_number . '.pdf';

        Pdf::view('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => $this->businessSettings(),
        ])
            ->format(self::INVOICE_FORMAT)
            ->margins(0, 0, 0, 0)
            ->disk('public')
            ->save($fileName);

        return $fileName;
    }

    /**
     * Genera el ticket termico de 80mm y lo guarda en el disco public.
     * Devuelve la ruta relativa dentro del disco.
     */
    public function renderTicket(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'order.items.product',
            'order.items.variant',
            'order.table',
            'payments',
        ]);

        $fileName = $this->ticketPath($invoice);

        Pdf::view('pdf.ticket', [
            'invoice' => $invoice,
            'settings' => $this->businessSettings(),
        ])
            ->driver('dompdf')
            ->paperSize(self::TICKET_WIDTH_MM, self::TICKET_HEIGHT_MM, 'mm')
            ->disk('public')
            ->save($fileName);

        return $fileName;
    }

    /**
     * Ruta relativa del ticket. Se deriva del numero de factura en vez de
     * guardarse en una columna propia: evita una migracion y el nombre ya es
     * unico porque el consecutivo lo garantiza el provider.
     */
    public function ticketPath(Invoice $invoice): string
    {
        return 'invoices/' . $invoice->invoice_number . '-ticket.pdf';
    }

    public function invoiceExists(Invoice $invoice): bool
    {
        return !empty($invoice->pdf_path)
            && Storage::disk('public')->exists($invoice->pdf_path);
    }

    public function ticketExists(Invoice $invoice): bool
    {
        return Storage::disk('public')->exists($this->ticketPath($invoice));
    }
}
