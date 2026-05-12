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

class DashboardController extends Controller
{
    public function __construct(
        private SystemInvoiceService $systemInvoiceService
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        
        $stats = [
            [
                'label' => 'Total Shops',
                'value' => (string) Shop::count(),
                'icon' => 'Store',
                'change' => '+2',
                'trendingUp' => true,
                'color' => 'text-blue-600',
                'bg' => 'bg-blue-100/50'
            ],
            [
                'label' => 'Total Newspapers',
                'value' => (string) Newspaper::count(),
                'icon' => 'Newspaper',
                'change' => '+1',
                'trendingUp' => true,
                'color' => 'text-purple-600',
                'bg' => 'bg-purple-100/50'
            ],
            [
                'label' => 'Total Invoices',
                'value' => (string) Invoice::count(),
                'icon' => 'FileText',
                'change' => '+18',
                'trendingUp' => true,
                'color' => 'text-amber-600',
                'bg' => 'bg-amber-100/50'
            ],
            [
                'label' => 'Total Revenue',
                'value' => 'Rs. ' . number_format(Invoice::sum('total_amount'), 2),
                'icon' => 'DollarSign',
                'change' => '+12.5%',
                'trendingUp' => true,
                'color' => 'text-emerald-600',
                'bg' => 'bg-emerald-100/50'
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
        ]);
    }
}
