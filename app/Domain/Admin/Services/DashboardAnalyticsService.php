<?php

namespace App\Domain\Admin\Services;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Shops\Models\Shop;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * Get monthly income trends for the past N months.
     */
    public function getMonthlyIncomeTrends(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Query invoices grouped by year-month
        $rawInvoices = Invoice::whereBetween('invoice_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Also fetch items for accurate gross and return totals
        $invoiceIds = $rawInvoices->pluck('id');
        $rawItems = InvoiceItem::whereIn('invoice_id', $invoiceIds)->get();

        // Group items by invoice_id
        $itemsByInvoice = $rawItems->groupBy('invoice_id');

        $monthlyData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthCarbon = Carbon::now()->subMonths($i);
            $yearMonth = $monthCarbon->format('Y-m');
            $label = $monthCarbon->format('M Y');
            $shortLabel = $monthCarbon->format('M');

            // Filter invoices for this month
            $monthInvoices = $rawInvoices->filter(function ($inv) use ($yearMonth) {
                return Carbon::parse($inv->invoice_date)->format('Y-m') === $yearMonth;
            });

            $grossSales = 0.0;
            $returnAmount = 0.0;
            $netIncome = 0.0;

            foreach ($monthInvoices as $inv) {
                $items = $itemsByInvoice->get($inv->id) ?? collect();
                
                $invGross = 0.0;
                $invReturn = 0.0;

                foreach ($items as $item) {
                    $invGross += (float) ($item->quantity * $item->unit_price);
                    $invReturn += (float) ($item->return_quantity * $item->unit_price);
                }

                // If items were empty, fallback to invoice columns
                if ($items->isEmpty()) {
                    $invReturn = (float) ($inv->return_total_amount ?? 0);
                    $invNet = (float) ($inv->total_net_amount ?? $inv->total_amount);
                    $invGross = $invNet + $invReturn;
                } else {
                    $invNet = $invGross - $invReturn;
                }

                $grossSales += $invGross;
                $returnAmount += $invReturn;
                $netIncome += $invNet;
            }

            $monthlyData[] = [
                'year_month' => $yearMonth,
                'label' => $label,
                'short_label' => $shortLabel,
                'gross_sales' => round($grossSales, 2),
                'returns' => round($returnAmount, 2),
                'net_income' => round($netIncome, 2),
                'invoice_count' => $monthInvoices->count(),
            ];
        }

        return $monthlyData;
    }

    /**
     * Get monthly performance metrics summary & MoM changes.
     */
    public function getMonthlyOverview(): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');

        $currentTrends = $this->getIncomeForPeriod($currentMonthStart, $currentMonthEnd);
        $prevTrends = $this->getIncomeForPeriod($prevMonthStart, $prevMonthEnd);

        $currentNet = $currentTrends['net_income'];
        $prevNet = $prevTrends['net_income'];

        $momGrowth = 0.0;
        if ($prevNet > 0) {
            $momGrowth = (($currentNet - $prevNet) / $prevNet) * 100;
        } else if ($currentNet > 0) {
            $momGrowth = 100.0;
        }

        $gross = $currentTrends['gross_sales'];
        $returns = $currentTrends['returns'];
        $returnPercent = $gross > 0 ? ($returns / $gross) * 100 : 0.0;

        $activeShopsCount = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
            ->distinct('shop_id')
            ->count('shop_id');

        $avgRevenuePerShop = $activeShopsCount > 0 ? $currentNet / $activeShopsCount : 0.0;

        return [
            'current_month_net' => round($currentNet, 2),
            'prev_month_net' => round($prevNet, 2),
            'mom_growth_percent' => round($momGrowth, 1),
            'current_month_gross' => round($gross, 2),
            'current_month_returns' => round($returns, 2),
            'return_percent' => round($returnPercent, 1),
            'active_shops_count' => $activeShopsCount,
            'avg_revenue_per_shop' => round($avgRevenuePerShop, 2),
            'current_month_invoices' => $currentTrends['invoice_count'],
        ];
    }

    /**
     * Get top revenue generating shops for the current month.
     */
    public function getTopShops(int $limit = 5): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $invoices = Invoice::with(['shop', 'items'])
            ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
            ->get();

        if ($invoices->isEmpty()) {
            // Fallback to all-time top shops if current month has no data
            $invoices = Invoice::with(['shop', 'items'])
                ->orderByDesc('invoice_date')
                ->limit(200)
                ->get();
        }

        $shopTotals = [];

        foreach ($invoices as $inv) {
            if (!$inv->shop) continue;
            $shopId = $inv->shop_id;
            $shopName = $inv->shop->name;

            if (!isset($shopTotals[$shopId])) {
                $shopTotals[$shopId] = [
                    'id' => $shopId,
                    'name' => $shopName,
                    'net_income' => 0.0,
                    'invoice_count' => 0,
                ];
            }

            $invNet = (float) $inv->total_amount;
            if ($inv->items && $inv->items->count() > 0) {
                $gross = $inv->items->sum(fn($item) => $item->quantity * $item->unit_price);
                $ret = $inv->items->sum(fn($item) => $item->return_quantity * $item->unit_price);
                $invNet = $gross - $ret;
            }

            $shopTotals[$shopId]['net_income'] += $invNet;
            $shopTotals[$shopId]['invoice_count'] += 1;
        }

        $sorted = collect($shopTotals)->sortByDesc('net_income')->take($limit)->values();

        $totalIncome = $sorted->sum('net_income');

        return $sorted->map(function ($item) use ($totalIncome) {
            $percentage = $totalIncome > 0 ? ($item['net_income'] / $totalIncome) * 100 : 0;
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'net_income' => round($item['net_income'], 2),
                'invoice_count' => $item['invoice_count'],
                'percentage' => round($percentage, 1),
            ];
        })->toArray();
    }

    /**
     * Get top distributed newspapers for the current month.
     */
    public function getTopNewspapers(int $limit = 5): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $items = InvoiceItem::with('newspaper')
            ->whereHas('invoice', function ($query) use ($currentMonthStart, $currentMonthEnd) {
                $query->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd]);
            })
            ->get();

        if ($items->isEmpty()) {
            // Fallback to recent items
            $items = InvoiceItem::with('newspaper')->latest()->limit(500)->get();
        }

        $grouped = [];

        foreach ($items as $item) {
            if (!$item->newspaper) continue;

            $newsId = $item->newspaper_id;
            $newsName = $item->newspaper->name;

            if (!isset($grouped[$newsId])) {
                $grouped[$newsId] = [
                    'id' => $newsId,
                    'name' => $newsName,
                    'quantity_sold' => 0,
                    'return_quantity' => 0,
                    'net_amount' => 0.0,
                ];
            }

            $sold = max(0, $item->quantity - $item->return_quantity);
            $net = $sold * (float) $item->unit_price;

            $grouped[$newsId]['quantity_sold'] += $sold;
            $grouped[$newsId]['return_quantity'] += $item->return_quantity;
            $grouped[$newsId]['net_amount'] += $net;
        }

        return collect($grouped)
            ->sortByDesc('net_amount')
            ->take($limit)
            ->values()
            ->map(function ($row) {
                return [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'quantity_sold' => $row['quantity_sold'],
                    'return_quantity' => $row['return_quantity'],
                    'net_amount' => round($row['net_amount'], 2),
                ];
            })
            ->toArray();
    }

    /**
     * Generate dynamic AI/smart business insights.
     */
    public function getSmartInsights(): array
    {
        $trends = $this->getMonthlyIncomeTrends(12);
        $overview = $this->getMonthlyOverview();
        $topShops = $this->getTopShops(1);
        $topNewspapers = $this->getTopNewspapers(1);

        $insights = [];

        // Insight 1: MoM Growth
        $growth = $overview['mom_growth_percent'];
        if ($growth > 0) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Positive Revenue Growth',
                'message' => "Monthly net income increased by {$growth}% compared to last month.",
                'icon' => 'TrendingUp'
            ];
        } else if ($growth < 0) {
            $absGrowth = abs($growth);
            $insights[] = [
                'type' => 'warning',
                'title' => 'Revenue Contraction',
                'message' => "Monthly net income decreased by {$absGrowth}% compared to last month.",
                'icon' => 'TrendingDown'
            ];
        } else {
            $insights[] = [
                'type' => 'info',
                'title' => 'Steady Revenue',
                'message' => 'Monthly revenue is performing at consistent levels compared to last month.',
                'icon' => 'Minus'
            ];
        }

        // Insight 2: Peak Month
        $peakMonth = collect($trends)->sortByDesc('net_income')->first();
        if ($peakMonth && $peakMonth['net_income'] > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Peak Monthly Performance',
                'message' => "{$peakMonth['label']} was your highest performing month with Rs. " . number_format($peakMonth['net_income'], 2) . " in net revenue.",
                'icon' => 'Award'
            ];
        }

        // Insight 3: Return Rate Analysis
        $returnPercent = $overview['return_percent'];
        if ($returnPercent > 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High Newspaper Return Rate',
                'message' => "Return rate is currently at {$returnPercent}%. Consider reviewing outlet distribution volumes to lower return loss.",
                'icon' => 'AlertTriangle'
            ];
        } else {
            $insights[] = [
                'type' => 'success',
                'title' => 'Healthy Return Rate',
                'message' => "Current return rate is controlled at {$returnPercent}%, maintaining optimal profit margins.",
                'icon' => 'CheckCircle2'
            ];
        }

        // Insight 4: Top Outlet Partner
        if (!empty($topShops)) {
            $topShop = $topShops[0];
            $insights[] = [
                'type' => 'info',
                'title' => 'Top Outlet Partner',
                'message' => "{$topShop['name']} generated Rs. " . number_format($topShop['net_income'], 2) . " leading sales contributions.",
                'icon' => 'Store'
            ];
        }

        return $insights;
    }

    /**
     * Helper to compute income for a given start and end date range.
     */
    private function getIncomeForPeriod(string $startDate, string $endDate): array
    {
        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])->get();
        $invoiceIds = $invoices->pluck('id');
        $items = InvoiceItem::whereIn('invoice_id', $invoiceIds)->get()->groupBy('invoice_id');

        $grossSales = 0.0;
        $returnAmount = 0.0;

        foreach ($invoices as $inv) {
            $invItems = $items->get($inv->id) ?? collect();
            $invGross = 0.0;
            $invReturn = 0.0;

            foreach ($invItems as $item) {
                $invGross += (float) ($item->quantity * $item->unit_price);
                $invReturn += (float) ($item->return_quantity * $item->unit_price);
            }

            if ($invItems->isEmpty()) {
                $invReturn = (float) ($inv->return_total_amount ?? 0);
                $invNet = (float) ($inv->total_net_amount ?? $inv->total_amount);
                $invGross = $invNet + $invReturn;
            }

            $grossSales += $invGross;
            $returnAmount += $invReturn;
        }

        $netIncome = $grossSales - $returnAmount;

        return [
            'gross_sales' => $grossSales,
            'returns' => $returnAmount,
            'net_income' => $netIncome,
            'invoice_count' => $invoices->count(),
        ];
    }
}
