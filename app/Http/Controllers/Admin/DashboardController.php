<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Services\SystemInvoiceService;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Shops\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

use App\Domain\Admin\Services\DashboardAnalyticsService;

class DashboardController extends Controller
{
    public function __construct(
        private SystemInvoiceService $systemInvoiceService,
        private DashboardAnalyticsService $analyticsService
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        
        $monthlyOverview = $this->analyticsService->getMonthlyOverview();
        $momGrowth = $monthlyOverview['mom_growth_percent'];

        $stats = [
            [
                'label' => 'Total Outlets',
                'value' => (string) Shop::count(),
                'icon' => 'Store',
                'change' => $monthlyOverview['active_shops_count'] . ' active this mo.',
                'trendingUp' => true,
                'color' => 'text-blue-600',
                'bg' => 'bg-blue-100/50 dark:bg-blue-900/30'
            ],
            [
                'label' => 'Newspapers Catalog',
                'value' => (string) Newspaper::count(),
                'icon' => 'Newspaper',
                'change' => 'Active',
                'trendingUp' => true,
                'color' => 'text-purple-600',
                'bg' => 'bg-purple-100/50 dark:bg-purple-900/30'
            ],
            [
                'label' => 'Monthly Net Income',
                'value' => 'Rs. ' . number_format($monthlyOverview['current_month_net'], 2),
                'icon' => 'DollarSign',
                'change' => ($momGrowth >= 0 ? '+' : '') . $momGrowth . '% MoM',
                'trendingUp' => $momGrowth >= 0,
                'color' => 'text-emerald-600',
                'bg' => 'bg-emerald-100/50 dark:bg-emerald-900/30'
            ],
            [
                'label' => 'Total Invoices Issued',
                'value' => (string) Invoice::count(),
                'icon' => 'FileText',
                'change' => $monthlyOverview['current_month_invoices'] . ' this mo.',
                'trendingUp' => true,
                'color' => 'text-amber-600',
                'bg' => 'bg-amber-100/50 dark:bg-amber-900/30'
            ],
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
            'monthlyTrends' => $this->analyticsService->getMonthlyIncomeTrends(12),
            'monthlyOverview' => $monthlyOverview,
            'topShops' => $this->analyticsService->getTopShops(5),
            'topNewspapers' => $this->analyticsService->getTopNewspapers(5),
            'smartInsights' => $this->analyticsService->getSmartInsights(),
        ]);
    }
}
