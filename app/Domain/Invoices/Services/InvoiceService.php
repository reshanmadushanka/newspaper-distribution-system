<?php

namespace App\Domain\Invoices\Services;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Domain\Newspapers\Repositories\NewspaperRepositoryInterface;
use App\Domain\Shops\Repositories\ShopRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class InvoiceService
{
    private const CACHE_TTL = 3600;
    private const CACHE_KEY_PREFIX = 'daily_report_';

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NewspaperRepositoryInterface $newspaperRepository,
        private ShopRepositoryInterface $shopRepository,
    ) {}

    public function getPaginatedInvoices(int $perPage = 10): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($perPage);
    }

    public function createInvoice(array $data, int $userId): Invoice
    {
        $invoiceData = [
            'invoice_date' => $data['invoice_date'],
            'shop_id' => $data['shop_id'],
            'created_by' => $userId,
            'total_amount' => 0,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ];

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'newspaper_id' => $item['newspaper_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ];
        }

        $invoice = $this->invoiceRepository->createWithItems($invoiceData, $items);

        $this->clearDailyReportCache($data['invoice_date']);

        return $invoice;
    }

    public function getInvoiceForView(int $id): Invoice
    {
        return $this->invoiceRepository->findOrFail($id);
    }

    public function getActiveNewspapers(): Collection
    {
        return $this->newspaperRepository->getActiveNewspapers(['id', 'name', 'price']);
    }

    public function getPreviousWeekSummary(string $date, int $shopId): ?Invoice
    {
        $previousWeekDate = date('Y-m-d', strtotime($date . ' - 7 days'));
        return $this->invoiceRepository->findByDateAndShop($previousWeekDate, $shopId);
    }

    public function markAsPaid(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);
        $updated = $this->invoiceRepository->updateStatus($id, 'paid');

        $this->clearDailyReportCache($invoice->invoice_date);

        return $updated;
    }

    public function getInvoiceForEdit(int $id): Invoice
    {
        return $this->invoiceRepository->findOrFail($id);
    }

    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        $invoiceData = [
            'invoice_date' => $data['invoice_date'],
            'total_amount' => 0,
            'notes' => $data['notes'] ?? null,
        ];

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'newspaper_id' => $item['newspaper_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ];
        }

        $updated = $this->invoiceRepository->updateWithItems($id, $invoiceData, $items);

        $this->clearDailyReportCache($invoice->invoice_date);
        $this->clearDailyReportCache($updated->invoice_date);

        return $updated;
    }

    public function deleteInvoice(int $id): bool
    {
        $invoice = $this->invoiceRepository->findOrFail($id);
        $result = $this->invoiceRepository->delete($id);

        $this->clearDailyReportCache($invoice->invoice_date);

        return $result;
    }

    public function getDailyReport(string $date): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $date;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            $invoices = $this->invoiceRepository->getDailyReportInvoices($date);

            $calculateItemProfit = function ($item) {
                $revenue = (float) $item->total_price;
                $cost = $item->newspaper && $item->newspaper->cost_price
                    ? (float) $item->quantity * (float) $item->newspaper->cost_price
                    : 0;
                return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost];
            };

            $calculateInvoiceProfit = function ($inv) use ($calculateItemProfit) {
                $revenue = 0;
                $cost = 0;
                foreach ($inv->items as $item) {
                    $p = $calculateItemProfit($item);
                    $revenue += $p['revenue'];
                    $cost += $p['cost'];
                }
                return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost];
            };

            $totalRevenue = 0;
            $totalCost = 0;
            foreach ($invoices as $inv) {
                $p = $calculateInvoiceProfit($inv);
                $totalRevenue += $p['revenue'];
                $totalCost += $p['cost'];
            }
            $totalProfit = $totalRevenue - $totalCost;
            $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

            $summary = [
                'total_invoices' => $invoices->count(),
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'profit_margin' => $profitMargin,
                'total_items' => $invoices->sum(fn($inv) => $inv->items->count()),
                'total_quantity' => $invoices->sum(fn($inv) => $inv->items->sum('quantity')),
                'paid_revenue' => 0,
                'draft_revenue' => 0,
                'paid_count' => $invoices->where('status', 'paid')->count(),
                'draft_count' => $invoices->where('status', 'draft')->count(),
            ];

            foreach ($invoices as $inv) {
                $p = $calculateInvoiceProfit($inv);
                if ($inv->status === 'paid') {
                    $summary['paid_revenue'] += $p['revenue'];
                } else {
                    $summary['draft_revenue'] += $p['revenue'];
                }
            }

            $byShop = $invoices->groupBy('shop_id')->map(function ($shopInvoices, $shopId) use ($calculateInvoiceProfit) {
                $shop = $shopInvoices->first()->shop;
                $revenue = 0;
                $cost = 0;
                foreach ($shopInvoices as $inv) {
                    $p = $calculateInvoiceProfit($inv);
                    $revenue += $p['revenue'];
                    $cost += $p['cost'];
                }
                $profit = $revenue - $cost;
                $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
                return [
                    'shop_id' => $shopId,
                    'shop_name' => $shop->name,
                    'invoice_count' => $shopInvoices->count(),
                    'total_revenue' => $revenue,
                    'total_cost' => $cost,
                    'total_profit' => $profit,
                    'profit_margin' => $margin,
                    'items_count' => $shopInvoices->sum(fn($inv) => $inv->items->count()),
                    'quantity' => $shopInvoices->sum(fn($inv) => $inv->items->sum('quantity')),
                ];
            })->values();

            $invoicesWithProfit = $invoices->map(function ($inv) use ($calculateInvoiceProfit) {
                $p = $calculateInvoiceProfit($inv);
                return [
                    'id' => $inv->id,
                    'shop_name' => $inv->shop->name,
                    'status' => $inv->status,
                    'items_count' => $inv->items->count(),
                    'quantity' => $inv->items->sum('quantity'),
                    'total_revenue' => $p['revenue'],
                    'total_cost' => $p['cost'],
                    'total_profit' => $p['profit'],
                    'profit_margin' => $p['revenue'] > 0 ? round(($p['profit'] / $p['revenue']) * 100, 1) : 0,
                ];
            });

            return [
                'date' => $date,
                'summary' => $summary,
                'by_shop' => $byShop->toArray(),
                'invoices' => $invoicesWithProfit->toArray(),
                'all_shops' => $this->shopRepository->getActiveShops()->toArray(),
            ];
        });
    }

    private function clearDailyReportCache(string $date): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $date);
    }

    public function checkInvoiceExistsForDateAndShop(string $date, int $shopId, ?int $excludeId = null): bool
    {
        return $this->invoiceRepository->existsByDateAndShop($date, $shopId, $excludeId);
    }
}
