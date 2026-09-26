<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CashMovement;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        ];
    }
}
