<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        /* Paleta Dulce Helado. El render lo hace Chromium, asi que aqui si
           se pueden usar flexbox y grid, que DomPDF no soporta. */
        :root {
            --aguamarina: #45AAA7;
            --aguamarina-600: #3B8C89;
            --aguamarina-100: #DDF3F0;
            --aguamarina-50: #F2FAF9;
            --petrol: #205B66;
            --petrol-600: #2E6672;
            --niebla: #718A98;
            --borde: #DCE8EB;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: var(--petrol);
            font-size: 12px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            padding: 34px 38px;
        }

        /* ---------- Encabezado ---------- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--aguamarina);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--aguamarina);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 21px;
            font-weight: 700;
            color: var(--petrol);
            letter-spacing: -0.2px;
        }

        .brand-tagline {
            font-size: 11px;
            color: var(--niebla);
            margin-top: 1px;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--petrol);
            letter-spacing: -0.3px;
        }

        .doc-number {
            margin-top: 4px;
            font-size: 14px;
            font-weight: 700;
            color: var(--aguamarina-600);
        }

        /* ---------- Distintivos ---------- */
        .badges {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            margin-top: 8px;
        }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .badge-internal {
            background: var(--aguamarina-100);
            color: var(--aguamarina-600);
        }

        .badge-dian {
            background: #E8F5EC;
            color: #2E7D4F;
        }

        .badge-pending {
            background: #FFF4E0;
            color: #B07514;
        }

        /* ---------- Bloques de datos ---------- */
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 22px;
        }

        .card {
            background: var(--aguamarina-50);
            border: 1px solid var(--borde);
            border-radius: 10px;
            padding: 13px 15px;
        }

        .card h2 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--aguamarina-600);
            margin-bottom: 7px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 11.5px;
            padding: 2px 0;
        }

        .row span:first-child {
            color: var(--niebla);
        }

        .row span:last-child {
            font-weight: 600;
            text-align: right;
        }

        /* ---------- Tabla de productos ---------- */
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        .items thead th {
            background: var(--aguamarina);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 9px 11px;
        }

        .items thead th:first-child {
            border-top-left-radius: 8px;
        }

        .items thead th:last-child {
            border-top-right-radius: 8px;
            text-align: right;
        }

        .items tbody td {
            padding: 9px 11px;
            border-bottom: 1px solid var(--borde);
            vertical-align: top;
        }

        .items tbody tr:nth-child(even) {
            background: var(--aguamarina-50);
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .item-name {
            font-weight: 600;
        }

        .item-note {
            font-size: 10px;
            color: var(--niebla);
            font-style: italic;
            margin-top: 1px;
        }

        /* ---------- Totales y pagos ---------- */
        .bottom {
            display: grid;
            grid-template-columns: 1fr 250px;
            gap: 20px;
            margin-top: 20px;
        }

        .totals {
            border-top: 2px solid var(--aguamarina);
            padding-top: 10px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            padding: 3px 0;
        }

        .total-line span:first-child {
            color: var(--niebla);
        }

        .total-grand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding: 11px 14px;
            background: var(--aguamarina);
            color: #fff;
            border-radius: 9px;
        }

        .total-grand span:first-child {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .total-grand span:last-child {
            font-size: 17px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        /* ---------- Pie ---------- */
        .footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid var(--borde);
            text-align: center;
        }

        .footer-brand {
            font-size: 13px;
            font-weight: 700;
            color: var(--petrol);
        }

        .footer-note {
            font-size: 10px;
            color: var(--niebla);
            margin-top: 3px;
        }

        .footer-cufe {
            margin-top: 8px;
            font-size: 8.5px;
            color: var(--niebla);
            word-break: break-all;
            font-family: 'Consolas', 'Courier New', monospace;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <!-- Encabezado -->
        <div class="header">
            <div class="brand">
                <div class="brand-mark">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h18a9 9 0 0 1-9 9 9 9 0 0 1-9-9Z" />
                        <path d="M12 12c0-2.5 1.5-4 3.5-4S19 9 19 10.5" />
                        <path d="M12 12c0-2.5-1.5-4-3.5-4S5 9 5 10.5" />
                    </svg>
                </div>
                <div>
                    <div class="brand-name">{{ $settings['shop_name'] ?? 'Dulce Helado' }}</div>
                    <div class="brand-tagline">Más que helados, momentos felices</div>
                </div>
            </div>

            <div class="doc-title">
                <h1>Factura</h1>
                <div class="doc-number">{{ $invoice->invoice_number }}</div>
                <div class="badges">
                    @if (($invoice->billing_mode ?? 'internal') === 'dian')
                        <span class="badge badge-dian">DIAN UBL 2.1</span>
                    @else
                        <span class="badge badge-internal">Factura interna</span>
                    @endif
                    @if (($invoice->dian_status ?? null) === 'pending')
                        <span class="badge badge-pending">En cola DIAN</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Datos del emisor y del cliente -->
        <div class="parties">
            <div class="card">
                <h2>Emisor</h2>
                <div class="row"><span>NIT</span><span>{{ $settings['shop_nit'] ?? '—' }}</span></div>
                <div class="row"><span>Dirección</span><span>{{ $settings['shop_address'] ?? '—' }}</span></div>
                <div class="row"><span>Teléfono</span><span>{{ $settings['shop_phone'] ?? '—' }}</span></div>
                <div class="row"><span>Emitida</span><span>{{ optional($invoice->created_at)->format('d/m/Y h:i A') }}</span></div>
            </div>

            <div class="card">
                <h2>Cliente</h2>
                <div class="row"><span>Nombre</span><span>{{ $invoice->customer_name ?? 'Consumidor Final' }}</span></div>
                <div class="row">
                    <span>{{ $invoice->customer_doc_type ?? 'CC' }}</span>
                    <span>{{ $invoice->customer_doc_number ?? '—' }}</span>
                </div>
                @if ($invoice->order?->isDelivery())
                    @if ($invoice->customer_phone)
                        <div class="row"><span>Teléfono</span><span>{{ $invoice->customer_phone }}</span></div>
                    @endif
                    <div class="row"><span>Dirección</span><span>{{ $invoice->order->delivery_address }}</span></div>
                    @if ($invoice->order->delivery_notes)
                        <div class="row"><span>Referencias</span><span>{{ $invoice->order->delivery_notes }}</span></div>
                    @endif
                    <div class="row"><span>Origen</span><span>Domicilio</span></div>
                @elseif ($invoice->order?->table)
                    <div class="row"><span>Mesa</span><span>{{ $invoice->order->table->name }}</span></div>
                @else
                    <div class="row"><span>Origen</span><span>Venta para llevar</span></div>
                @endif
                <div class="row"><span>Orden</span><span>{{ $invoice->order?->order_number ?? '—' }}</span></div>
            </div>
        </div>

        <!-- Productos -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 46%">Producto</th>
                    <th style="width: 12%" class="num">Cant.</th>
                    <th style="width: 21%" class="num">Precio unit.</th>
                    <th style="width: 21%" class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->order?->items ?? [] as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->product?->name ?? 'Producto' }}</div>
                            @if ($item->variant)
                                <div class="item-note">{{ $item->variant->name }}</div>
                            @endif
                            @if ($item->notes)
                                <div class="item-note">* {{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="num">$ {{ number_format((float) $item->unit_price, 0) }}</td>
                        <td class="num">$ {{ number_format((float) $item->subtotal, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:#718A98;padding:18px">
                            Sin productos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totales y pagos -->
        <div class="bottom">
            <div>
                @if ($invoice->payments->isNotEmpty())
                    <h2 style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#3B8C89;margin-bottom:7px">
                        Formas de pago
                    </h2>
                    <div class="card" style="padding:11px 14px">
                        @foreach ($invoice->payments as $payment)
                            <div class="row">
                                <span>{{ ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'][$payment->payment_method] ?? ucfirst($payment->payment_method) }}</span>
                                <span>$ {{ number_format((float) $payment->amount, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="totals">
                <div class="total-line"><span>Subtotal</span><span>$ {{ number_format((float) $invoice->subtotal, 0) }}</span></div>
                @if ((float) $invoice->discount_amount > 0)
                    <div class="total-line"><span>Descuento</span><span>-$ {{ number_format((float) $invoice->discount_amount, 0) }}</span></div>
                @endif
                @if ((float) $invoice->tax_amount > 0)
                    <div class="total-line"><span>Impuestos</span><span>$ {{ number_format((float) $invoice->tax_amount, 0) }}</span></div>
                @endif
                @if ((float) $invoice->tip_amount > 0)
                    <div class="total-line"><span>Propina</span><span>$ {{ number_format((float) $invoice->tip_amount, 0) }}</span></div>
                @endif
                @if ($invoice->order && (float) $invoice->order->delivery_fee > 0)
                    <div class="total-line"><span>Domicilio</span><span>$ {{ number_format((float) $invoice->order->delivery_fee, 0) }}</span></div>
                @endif
                <div class="total-grand">
                    <span>Total</span>
                    <span>$ {{ number_format((float) $invoice->total, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Pie -->
        <div class="footer">
            <div class="footer-brand">{{ $settings['shop_name'] ?? 'Dulce Helado' }}</div>
            <div class="footer-note">Gracias por su compra. Conserve este comprobante.</div>
            @if (!empty($invoice->dian_cufe))
                <div class="footer-cufe">CUFE: {{ $invoice->dian_cufe }}</div>
            @endif
        </div>
    </div>
</body>

</html>
