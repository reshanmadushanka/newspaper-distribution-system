<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Services\SystemInvoiceService;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Shops\Models\Shop;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const array RANGES = ['7d' => 7, '30d' => 30, '90d' => 90];

    public function __construct(
        private SystemInvoiceService $systemInvoiceService
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();

        $range = $request->query('range');
        if (! \array_key_exists($range, self::RANGES)) {
            $range = '30d';
        }
        $rangeDays = self::RANGES[$range];

        $now = CarbonImmutable::now();
        $currentStart = $now->subDays($rangeDays);
        $previousStart = $now->subDays($rangeDays * 2);

        $shopsCurrent = Shop::where('created_at', '>=', $currentStart)->count();
        $shopsPrevious = Shop::whereBetween('created_at', [$previousStart, $currentStart])->count();

        $newspapersCurrent = Newspaper::where('created_at', '>=', $currentStart)->count();
        $newspapersPrevious = Newspaper::whereBetween('created_at', [$previousStart, $currentStart])->count();

        $invoicesCurrent = Invoice::where('invoice_date', '>=', $currentStart)->count();
        $invoicesPrevious = Invoice::whereBetween('invoice_date', [$previousStart, $currentStart])->count();

        $revenueCurrent = (float) Invoice::where('invoice_date', '>=', $currentStart)->sum('total_amount');
        $revenuePrevious = (float) Invoice::whereBetween('invoice_date', [$previousStart, $currentStart])->sum('total_amount');

        $stats = [
            array_merge([
                'label' => 'Total Shops',
                'value' => (string) Shop::count(),
                'icon' => 'Store',
                'color' => 'text-blue-600',
                'bg' => 'bg-blue-100/50',
            ], $this->buildTrend($shopsCurrent, $shopsPrevious)),
            array_merge([
                'label' => 'Total Newspapers',
                'value' => (string) Newspaper::count(),
                'icon' => 'Newspaper',
                'color' => 'text-purple-600',
                'bg' => 'bg-purple-100/50',
            ], $this->buildTrend($newspapersCurrent, $newspapersPrevious)),
            array_merge([
                'label' => 'Total Invoices',
                'value' => (string) Invoice::count(),
                'icon' => 'FileText',
                'color' => 'text-amber-600',
                'bg' => 'bg-amber-100/50',
            ], $this->buildTrend($invoicesCurrent, $invoicesPrevious)),
            array_merge([
                'label' => 'Total Revenue',
                'value' => 'Rs. ' . number_format(Invoice::sum('total_amount'), 2),
                'icon' => 'DollarSign',
                'color' => 'text-emerald-600',
                'bg' => 'bg-emerald-100/50',
            ], $this->buildTrend($revenueCurrent, $revenuePrevious)),
        ];

        $recentInvoices = Invoice::with(['shop'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Get pending system invoices for this admin
        $pendingSystemInvoices = SystemInvoice::forAdmin($user)
            ->pending()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentInvoices' => $recentInvoices,
            'pendingSystemInvoices' => $pendingSystemInvoices,
            'systemInvoiceStats' => $this->systemInvoiceService->getDashboardStats($user),
            'range' => $range,
            'chartData' => $this->buildChartData($currentStart, $rangeDays),
        ]);
    }

    private function buildChartData(CarbonImmutable $start, int $days): array
    {
        $daily = Invoice::query()
            ->where('invoice_date', '>=', $start)
            ->selectRaw('DATE(invoice_date) as day, SUM(total_amount) as revenue, COUNT(*) as invoices')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i)->toDateString();
            $row = $daily->get($date);
            $series[] = [
                'date' => $date,
                'revenue' => $row ? (float) $row->revenue : 0.0,
                'invoices' => $row ? (int) $row->invoices : 0,
            ];
        }

        return $series;
    }

    private function buildTrend(float $current, float $previous): array
    {
        if ($previous == 0.0 && $current == 0.0) {
            return ['change' => null, 'trendingUp' => true];
        }

        if ($previous == 0.0) {
            return ['change' => 'New', 'trendingUp' => true];
        }

        $pct = (($current - $previous) / $previous) * 100;
        $sign = $pct >= 0 ? '+' : '';

        return [
            'change' => $sign . number_format($pct, 1) . '%',
            'trendingUp' => $pct >= 0,
        ];
    }
}
