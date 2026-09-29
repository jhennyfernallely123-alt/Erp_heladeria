<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        // Total sales from invoices
        $totalSales = (float) Invoice::whereBetween('created_at', [$start, $end])->sum('total');
        $invoiceCount = Invoice::whereBetween('created_at', [$start, $end])->count();
        $averageTicket = $invoiceCount > 0 ? $totalSales / $invoiceCount : 0.00;

        // Payment methods breakdown
        $paymentMethods = Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupBy('payments.payment_method')
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'))
            ->get();

        // Expenses / Cash Out movements
        $totalExpenses = (float) CashMovement::where('type', 'cash_out')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        // Gross Profit Estimation (Sales - Cost of products sold)
        $costOfGoodsSold = (float) DB::table('order_items')
            ->join('invoices', 'order_items.order_id', '=', 'invoices.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->select(DB::raw('SUM(order_items.quantity * COALESCE(product_variants.cost_price, products.cost_price)) as total_cost'))
            ->value('total_cost');

        $grossMargin = $totalSales - $costOfGoodsSold;
        $netCashFlow = $totalSales - $totalExpenses;

        // Series para los graficos: ventas por dia, separadas en efectivo y
        // otros medios, mas los egresos del mismo dia. Se agrupan por DATE()
        // en la base y no en PHP: con un rango de un anio son miles de facturas
        // y agrupar en memoria es tirar ciclos.
        $daily = DB::table('invoices')
            ->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupByRaw('DATE(invoices.created_at)')
            ->select([
                DB::raw('DATE(invoices.created_at) as day'),
                DB::raw('SUM(invoices.total) as total'),
                DB::raw('SUM(CASE WHEN payments.payment_method = \'cash\' THEN payments.amount ELSE 0 END) as cash'),
                DB::raw('SUM(CASE WHEN payments.payment_method <> \'cash\' OR payments.payment_method IS NULL THEN payments.amount ELSE 0 END) as other'),
            ])
            ->orderBy('day')
            ->get();

        $expensesByDay = DB::table('cash_movements')
            ->where('type', 'cash_out')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->select([
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(amount) as total'),
            ])
            ->get()
            ->keyBy('day');

        // Se arman las series completas: si un dia no aparece en la consulta,
        // igual tiene que estar en el grafico con cero, o el eje de tiempo
        // muestra saltos y el admin cree que no hubo venta ese dia.
        $dailySeries = $this->fillDateRange($start, $end, $daily, $expensesByDay);

        // Top 5 best selling ice creams / products
        $topProducts = OrderItem::join('invoices', 'order_items.order_id', '=', 'invoices.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupBy('order_items.product_id', 'products.name')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_sales'))
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'total_sales' => $totalSales,
            'invoice_count' => $invoiceCount,
            'average_ticket' => round($averageTicket, 2),
            'total_expenses' => $totalExpenses,
            'cost_of_goods' => round($costOfGoodsSold, 2),
            'gross_margin' => round($grossMargin, 2),
            'net_cash_flow' => round($netCashFlow, 2),
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
            'daily' => $dailySeries,
        ];
    }

    /**
     * Serie diaria completa, sin huecos.
     *
     * Cada dia del rango aparece con su total, efectivo, otros medios y
     * egresos. Los dias sin movimiento van en cero: un grafico de barras con
     * espacios en blanco hace pensar que falta informacion, cuando en realidad
     * es que no se vendio.
     *
     * El rango se limita a 366 dias porque un grafico de un anio con un eje
     * por dia es ilegible, y el filtro de la vista ya limita el rango.
     */
    private function fillDateRange(Carbon $start, Carbon $end, $sales, $expenses): array
    {
        $salesByDay = $sales->keyBy('day');
        $maxDays = 366;

        $series = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        $count = 0;

        while ($cursor->lte($last) && $count < $maxDays) {
            $day = $cursor->toDateString();
            $sale = $salesByDay->get($day);
            $expense = $expenses->get($day);

            $series[] = [
                'day' => $day,
                'label' => $cursor->translatedFormat('d/m'),
                'total' => round((float) ($sale->total ?? 0), 2),
                'cash' => round((float) ($sale->cash ?? 0), 2),
                'other' => round((float) ($sale->other ?? 0), 2),
                'expenses' => round((float) ($expense->total ?? 0), 2),
            ];

            $cursor->addDay();
            $count++;
        }

        return $series;
    }
}
