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
    private const RANGE_CACHE_TTL = 600;
    private const CACHE_KEY_PREFIX = 'daily_report_';
    private const CACHE_KEY_BY_SHOP = 'report_by_shop_';
    private const CACHE_KEY_BY_NEWSPAPER = 'report_by_newspaper_';
    private const CACHE_KEY_INVOICE_LIST = 'report_invoice_list_';

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NewspaperRepositoryInterface $newspaperRepository,
        private ShopRepositoryInterface $shopRepository,
    ) {}

    public function getPaginatedInvoices(
        int $perPage = 10,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $invoiceType = null
    ): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($perPage, $search, $dateFrom, $dateTo, $invoiceType);
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
            'invoice_type' => $data['invoice_type'] ?? 'daily',
            'previous_deficit' => $data['previous_deficit'] ?? 0,
            'special_discount' => $data['special_discount'] ?? 0,
        ];

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'newspaper_id' => $item['newspaper_id'],
                'price_id' => $item['price_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'return_quantity' => $item['return_quantity'] ?? 0,
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
        return $this->newspaperRepository->getActiveNewspapers(['id', 'name']);
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

    public function markAsPrinted(int $id): Invoice
    {
        return $this->invoiceRepository->markAsPrinted($id);
    }

    public function getInvoiceForEdit(int $id): Invoice
    {
        return $this->invoiceRepository->findOrFail($id);
    }

    public function deleteInvoiceItem(int $invoiceId, int $itemId): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($invoiceId);

        $item = $this->invoiceRepository->findItem($itemId);
        if (!$item || $item->invoice_id !== $invoice->id) {
            throw new \InvalidArgumentException('Item not found in this invoice.');
        }

        if ($invoice->items()->count() <= 1) {
            throw new \RuntimeException('Cannot delete the last item from an invoice.');
        }

        // Delete the item
        $this->invoiceRepository->deleteItem($itemId);

        // Recalculate totals and return updated invoice
        $updated = $this->invoiceRepository->recalculateTotals($invoiceId);

        // clear cache for the invoice date
        $this->clearDailyReportCache($invoice->invoice_date);

        return $updated;
    }

    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        $invoiceData = [
            'invoice_date' => $data['invoice_date'],
            'total_amount' => 0,
            'notes' => $data['notes'] ?? null,
            'invoice_type' => $data['invoice_type'] ?? null,
            'previous_deficit' => $data['previous_deficit'] ?? 0,
            'special_discount' => $data['special_discount'] ?? 0,
        ];

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'newspaper_id' => $item['newspaper_id'],
                'price_id' => $item['price_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'return_quantity' => $item['return_quantity'] ?? 0,
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

    public function getByShopReport(string $dateFrom, string $dateTo, ?int $shopId = null, ?string $invoiceType = null): array
    {
        $cacheKey = self::CACHE_KEY_BY_SHOP . "{$dateFrom}_{$dateTo}_" . ($shopId ?? 'all') . '_' . ($invoiceType ?? 'all');

        return Cache::remember($cacheKey, self::RANGE_CACHE_TTL, function () use ($dateFrom, $dateTo, $shopId, $invoiceType) {
            $invoices = $this->invoiceRepository->getByShopReport($dateFrom, $dateTo, $shopId, $invoiceType);

            $totalRevenue = 0;
            $totalCost = 0;
            $totalQuantity = 0;

            $grouped = $invoices->groupBy('shop_id')->map(function ($shopInvoices) use (&$totalRevenue, &$totalCost, &$totalQuantity) {
                $shop = $shopInvoices->first()->shop;
                $sid = $shopInvoices->first()->shop_id;
                $revenue = 0;
                $cost = 0;
                $quantity = 0;

                foreach ($shopInvoices as $inv) {
                    $revenue += (float) $inv->total_amount;
                    $quantity += $inv->items->sum('quantity');
                    foreach ($inv->items as $item) {
                        $cost += $item->newspaper && $item->newspaper->cost_price
                            ? (float) $item->quantity * (float) $item->newspaper->cost_price
                            : 0;
                    }
                }

                $profit = $revenue - $cost;
                $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

                $totalRevenue += $revenue;
                $totalCost += $cost;
                $totalQuantity += $quantity;

                return [
                    'shop_id' => $sid,
                    'shop_name' => $shop->name,
                    'invoice_count' => $shopInvoices->count(),
                    'quantity' => $quantity,
                    'total_revenue' => $revenue,
                    'total_cost' => $cost,
                    'total_profit' => $profit,
                    'profit_margin' => $margin,
                ];
            })->values();

            $totalProfit = $totalRevenue - $totalCost;
            $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'summary' => [
                    'total_invoices' => $invoices->count(),
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'total_quantity' => $totalQuantity,
                ],
                'by_shop' => $grouped->toArray(),
            ];
        });
    }

    public function getByNewspaperReport(string $dateFrom, string $dateTo, ?int $newspaperId = null, ?string $invoiceType = null, ?int $shopId = null): array
    {
        $cacheKey = self::CACHE_KEY_BY_NEWSPAPER . "{$dateFrom}_{$dateTo}_" . ($newspaperId ?? 'all') . '_' . ($shopId ?? 'all') . '_' . ($invoiceType ?? 'all');

        return Cache::remember($cacheKey, self::RANGE_CACHE_TTL, function () use ($dateFrom, $dateTo, $newspaperId, $invoiceType, $shopId) {
            $items = $this->invoiceRepository->getByNewspaperReport($dateFrom, $dateTo, $newspaperId, $shopId, $invoiceType);

            $totalRevenue = 0;
            $totalCost = 0;
            $totalQuantity = 0;

            $grouped = $items->groupBy('newspaper_id')->map(function ($newspaperItems) use (&$totalRevenue, &$totalCost, &$totalQuantity) {
                $newspaper = $newspaperItems->first()->newspaper;
                $revenue = 0;
                $cost = 0;
                $quantity = 0;
                $invoiceIds = [];

                foreach ($newspaperItems as $item) {
                    $qty = (int) $item->quantity;
                    $revenue += (float) $item->total_price;
                    $cost += $item->newspaper && $item->newspaper->cost_price
                        ? (float) $qty * (float) $item->newspaper->cost_price
                        : 0;
                    $quantity += $qty;
                    $invoiceIds[$item->invoice_id] = true;
                }

                $profit = $revenue - $cost;
                $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

                $totalRevenue += $revenue;
                $totalCost += $cost;
                $totalQuantity += $quantity;

                return [
                    'newspaper_id' => $newspaper?->id ?? $newspaperItems->first()->newspaper_id,
                    'newspaper_name' => $newspaper?->name ?? 'Deleted newspaper',
                    'quantity' => $quantity,
                    'total_revenue' => $revenue,
                    'total_cost' => $cost,
                    'total_profit' => $profit,
                    'profit_margin' => $margin,
                    'invoice_count' => count($invoiceIds),
                ];
            })->sortBy('newspaper_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

            $totalProfit = $totalRevenue - $totalCost;
            $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'summary' => [
                    'total_quantity' => $totalQuantity,
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'total_newspapers' => $grouped->count(),
                ],
                'by_newspaper' => $grouped->toArray(),
            ];
        });
    }

    public function getInvoiceListReport(
        string $dateFrom,
        string $dateTo,
        ?int $shopId = null,
        ?int $newspaperId = null,
        ?string $invoiceType = null
    ): array
    {
        $cacheKey = self::CACHE_KEY_INVOICE_LIST . "{$dateFrom}_{$dateTo}_" . ($shopId ?? 'all') . '_' . ($newspaperId ?? 'all') . '_' . ($invoiceType ?? 'all');

        return Cache::remember($cacheKey, self::RANGE_CACHE_TTL, function () use ($dateFrom, $dateTo, $shopId, $newspaperId, $invoiceType) {
            $invoices = $this->invoiceRepository->getInvoiceList($dateFrom, $dateTo, $shopId, $newspaperId, $invoiceType);

            $totalAmount = 0;
            $totalQuantity = 0;

            $invoiceList = $invoices->map(function ($inv) use (&$totalAmount, &$totalQuantity) {
                $amount = (float) $inv->total_amount;
                $profit = round($amount * 0.12, 2);
                $quantity = (int) $inv->items->sum('quantity');
                $totalAmount += $amount;
                $totalQuantity += $quantity;

                return [
                    'id' => $inv->id,
                    'invoice_date' => $inv->invoice_date,
                    'shop_name' => $inv->shop->name,
                    'status' => $inv->status,
                    'items_count' => $quantity,
                    'total_amount' => $amount,
                    'profit' => $profit,
                ];
            });

            $totalProfit = round($totalAmount * 0.12, 2);

            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'summary' => [
                    'total_invoices' => $invoices->count(),
                    'total_quantity' => $totalQuantity,
                    'total_revenue' => $totalAmount,
                    'total_profit' => $totalProfit,
                ],
                'invoices' => $invoiceList->toArray(),
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

    public function getShopsWithoutInvoicesForDate(string $date): \Illuminate\Support\Collection
    {
        return $this->invoiceRepository->getShopsWithoutInvoicesForDate($date);
    }

    public function getShopsWithLastWeekInvoicesButNotForDate(string $targetDate): \Illuminate\Support\Collection
    {
        return $this->invoiceRepository->getShopsWithLastWeekInvoicesButNotForDate($targetDate);
    }

    public function dispatchInvoiceGeneration(string $targetDate, int $userId): array
    {
        // Get all active shops
        $shops = $this->shopRepository->getActiveShops();

        // Initialize progress tracking
        $cacheKey = "invoice_generation_{$userId}";
        $progress = [
            'total' => $shops->count(),
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'invoices' => [],
            'status' => 'processing',
            'started_at' => now()->toDateTimeString(),
        ];

        Cache::put($cacheKey, $progress, now()->addMinutes(30));

        // Dispatch jobs for each shop
        foreach ($shops as $shop) {
            \App\Jobs\GenerateInvoiceFromLastWeek::dispatch(
                $shop->id,
                $targetDate,
                $userId
            )->onQueue('default');
        }

        return [
            'message' => "Invoice generation started for {$shops->count()} shops",
            'total_shops' => $shops->count(),
            'target_date' => $targetDate,
        ];
    }

    public function getGenerationProgress(int $userId): array
    {
        $cacheKey = "invoice_generation_{$userId}";
        return Cache::get($cacheKey, [
            'status' => 'not_started',
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'invoices' => [],
        ]);
    }

    public function clearGenerationProgress(int $userId): void
    {
        $cacheKey = "invoice_generation_{$userId}";
        Cache::forget($cacheKey);
    }

}
