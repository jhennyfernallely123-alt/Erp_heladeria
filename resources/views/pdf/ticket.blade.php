<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #111;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .header { margin-bottom: 8px; border-bottom: 1px dashed #777; padding-bottom: 6px; }
        .shop-title { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 6px; }
        .details-table th { border-bottom: 1px dashed #777; padding: 3px 0; text-align: left; font-size: 10px; }
        .details-table td { padding: 3px 0; vertical-align: top; }
        .totals-table { width: 100%; border-collapse: collapse; margin-top: 4px; border-top: 1px dashed #777; padding-top: 4px; }
        .totals-table td { padding: 2px 0; }
        .footer { margin-top: 10px; border-top: 1px dashed #777; padding-top: 8px; font-size: 9px; }
        .cufe-box { font-size: 8px; word-break: break-all; margin-top: 5px; color: #444; }
    </style>
</head>
<body>
    <div class="header text-center">
        <div class="shop-title">{{ $settings['shop_name'] ?? 'HELADERÍA ARTESANAL' }}</div>
        <div>NIT: {{ $settings['shop_nit'] ?? '900.123.456-7' }}</div>
        <div>{{ $settings['shop_address'] ?? 'Calle Principal # 10 - 20' }}</div>
        <div>Tel: {{ $settings['shop_phone'] ?? '300 123 4567' }}</div>
        <div style="margin-top: 5px;">
            <span class="font-bold">{{ $invoice->billing_mode === 'dian' ? 'FACTURA ELECTRÓNICA DE VENTA' : 'TICKET DE VENTA INTERNO' }}</span>
        </div>
        <div class="font-bold">No. {{ $invoice->invoice_number }}</div>
        <div>Fecha: {{ $invoice->created_at->format('d/m/Y H:i:s') }}</div>
    </div>

    <div>
        <div><strong>Cliente:</strong> {{ $invoice->customer_name }}</div>
        <div><strong>Doc:</strong> {{ $invoice->customer_doc_type }} {{ $invoice->customer_doc_number }}</div>
        @if($invoice->order && $invoice->order->table)
            <div><strong>Mesa:</strong> {{ $invoice->order->table->name }}</div>
        @else
            <div><strong>Tipo:</strong> Venta Mostrador / Para Llevar</div>
        @endif
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th style="width: 50%;">Cant / Descripción</th>
                <th class="text-right" style="width: 25%;">Unit</th>
                <th class="text-right" style="width: 25%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->items as $item)
                <tr>
                    <td>
                        {{ number_format($item->quantity, 0) }}x {{ $item->product->name }}
                        @if($item->variant)
                            <br><small style="color:#555;">({{ $item->variant->name }})</small>
                        @endif
                        @if($item->notes)
                            <br><small style="color:#777; font-style:italic;">* {{ $item->notes }}</small>
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td>Descuento:</td>
            <td class="text-right">-${{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($invoice->tax_amount > 0)
        <tr>
            <td>Impuesto (IVA/INC):</td>
            <td class="text-right">${{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($invoice->tip_amount > 0)
        <tr>
            <td>Propina Voluntaria:</td>
            <td class="text-right">${{ number_format($invoice->tip_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="font-bold" style="font-size: 13px;">
            <td>TOTAL A PAGAR:</td>
            <td class="text-right">${{ number_format($invoice->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div style="margin-top: 6px; font-size: 10px;">
        <strong>Medios de Pago:</strong>
        @foreach($invoice->payments as $payment)
            <div>- {{ ucfirst($payment->payment_method) }}: ${{ number_format($payment->amount, 0, ',', '.') }}
                @if($payment->reference_code) (Ref: {{ $payment->reference_code }}) @endif
            </div>
        @endforeach
    </div>

    @if($invoice->dian_cufe)
    <div class="cufe-box">
        <strong>CUFE:</strong><br>{{ $invoice->dian_cufe }}
    </div>
    @endif

    <div class="footer text-center">
        <div>¡Gracias por su compra! Disfrute su helado.</div>
        <div>Software IceCream ERP v1.0</div>
    </div>
</body>
</html>
