<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Shops\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $invoiceType = null
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $invoiceId = ltrim($search, '#');

        return Invoice::query()
            ->with('shop')
            ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo]);
            })
            ->when($invoiceType, fn($query) => $query->where('invoice_type', $invoiceType))
            ->when($search !== '', function ($query) use ($search, $invoiceId) {
                $query->where(function ($query) use ($search, $invoiceId) {
                    if (ctype_digit($invoiceId)) {
                        $query->whereKey((int) $invoiceId);
                    }

                    $query->orWhereHas('shop', function ($query) use ($search) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'invoice_type' => $invoiceType,
            ]);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::with(['shop', 'items.newspaper', 'items.price', 'creator'])->find($id);
    }

    public function findOrFail(int $id): Invoice
    {
        return Invoice::with(['shop', 'items.newspaper', 'items.price', 'creator'])->findOrFail($id);
    }

    public function createWithItems(array $invoiceData, array $items): Invoice
    {
        return DB::transaction(function () use ($invoiceData, $items) {
            $invoice = Invoice::create($invoiceData);

            $invoiceItems = array_map(fn($item) => [
                'invoice_id'   => $invoice->id,
                'newspaper_id' => $item['newspaper_id'],
                'price_id'     => $item['price_id'] ?? null,
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'total_price'  => $item['quantity'] * $item['unit_price'],
                'return_quantity' => $item['return_quantity'] ?? 0,
                'return_total_price' => ($item['return_quantity'] ?? 0) * $item['unit_price'],
            ], $items);

            $invoice->items()->insert($invoiceItems);

            $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
            $totalReturnAmount = array_sum(array_column($invoiceItems, 'return_total_price'));
            $invoice->update(['total_amount' => $totalAmount - $totalReturnAmount]);

            return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
        });
    }

    public function findByDateAndShop(string $date, int $shopId): ?Invoice
    {
        return Invoice::query()
            ->with(['items.newspaper'])
            ->where('invoice_date', $date)
            ->where('shop_id', $shopId)
            ->first();
    }

    public function updateStatus(int $id, string $status): Invoice
    {
        $invoice = $this->findOrFail($id);
        $invoice->update(['status' => $status]);
        return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
    }

    public function markAsPrinted(int $id): Invoice
    {
        $invoice = $this->findOrFail($id);
        $invoice->update(['printed_at' => now()]);
        return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
    }

    public function updateWithItems(int $id, array $invoiceData, array $items): Invoice
    {
        return DB::transaction(function () use ($id, $invoiceData, $items) {
            $invoice = $this->findOrFail($id);
            $invoice->update($invoiceData);

            foreach ($items as $item) {
                $invoice->items()->updateOrCreate(
                    [
                        'invoice_id'   => $invoice->id,
                        'newspaper_id' => $item['newspaper_id'],
                    ],
                    [
                        'price_id'           => $item['price_id'] ?? null,
                        'quantity'           => $item['quantity'],
                        'unit_price'         => $item['unit_price'],
                        'total_price'        => $item['quantity'] * $item['unit_price'],
                        'return_quantity'    => $item['return_quantity'] ?? 0,
                        'return_total_price' => ($item['return_quantity'] ?? 0) * $item['unit_price'],
                    ]
                );
            }

            $finalItems        = $invoice->items()->get(['total_price', 'return_total_price']);
            $totalAmount       = (float) $finalItems->sum('total_price');
            $totalReturnAmount = (float) $finalItems->sum('return_total_price');

            $invoice->update([
                'total_amount' => $totalAmount,
                'total_net_amount' => $totalAmount - $totalReturnAmount + (float)($invoiceData['previous_deficit'] ?? 0) - (float)($invoiceData['special_discount'] ?? 0),
                'return_total_amount' => $totalReturnAmount
            ]);

            return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
        });
    }

    public function delete(int $id): bool
    {
        return Invoice::findOrFail($id)->delete();
    }

    public function findItem(int $id): ?InvoiceItem
    {
        return InvoiceItem::find($id);
    }

    public function deleteItem(int $id): bool
    {
        return InvoiceItem::findOrFail($id)->delete();
    }

    public function recalculateTotals(int $invoiceId): Invoice
    {
        $invoice = $this->findOrFail($invoiceId);

        $finalItems = $invoice->items()->get(['total_price', 'return_total_price']);
        $totalAmount = (float) $finalItems->sum('total_price');
        $totalReturnAmount = (float) $finalItems->sum('return_total_price');

        $invoice->update([
            'total_amount' => $totalAmount,
            'return_total_amount' => $totalReturnAmount,
            'total_net_amount' => $totalAmount - $totalReturnAmount,
        ]);

        return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
    }

    public function getDailyReportInvoices(string $date): Collection
    {
        return Invoice::with(['shop', 'items.newspaper'])
            ->where('invoice_date', $date)
            ->orderBy('shop_id')
            ->get();
    }

    public function getByShopReport(string $dateFrom, string $dateTo, ?int $shopId = null, ?string $invoiceType = null): Collection
    {
        return Invoice::with(['shop', 'items.newspaper'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($invoiceType, fn($q) => $q->where('invoice_type', $invoiceType))
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('shop_id')
            ->orderBy('invoice_date')
            ->get();
    }

    public function getByNewspaperReport(string $dateFrom, string $dateTo, ?int $newspaperId = null, ?int $shopId = null, ?string $invoiceType = null): Collection
    {
        return InvoiceItem::with(['invoice.shop', 'newspaper'])
            ->whereHas('invoice', fn($q) => $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                ->when($invoiceType, fn($inq) => $inq->where('invoice_type', $invoiceType))
                ->when($shopId, fn($inq) => $inq->where('shop_id', $shopId)))
            ->when($newspaperId, fn($q) => $q->where('newspaper_id', $newspaperId))
            ->get();
    }

    public function getInvoiceList(
        string $dateFrom,
        string $dateTo,
        ?int $shopId = null,
        ?int $newspaperId = null,
        ?string $invoiceType = null
    ): Collection {
        return Invoice::with(['shop', 'items'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($invoiceType, fn($q) => $q->where('invoice_type', $invoiceType))
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->when($newspaperId, fn($q) => $q->whereHas('items', fn($itemQuery) => $itemQuery->where('newspaper_id', $newspaperId)))
            ->orderBy('invoice_date')
            ->orderBy('shop_id')
            ->get();
    }

    public function existsByDateAndShop(string $date, int $shopId, ?int $excludeId = null): bool
    {
        return Invoice::where('invoice_date', $date)
            ->where('shop_id', $shopId)
            ->when($excludeId, fn($query) => $query->whereKeyNot($excludeId))
            ->exists();
    }

    public function getShopsWithoutInvoicesForDate(string $date): \Illuminate\Support\Collection
    {
        return Shop::where('status', 'active')
            ->whereNotExists(function ($query) use ($date) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('shops.id', 'invoices.shop_id')
                    ->where('invoices.invoice_date', $date)
                    ->whereNull('invoices.deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getShopsWithLastWeekInvoicesButNotForDate(string $targetDate): \Illuminate\Support\Collection
    {
        $lastWeekDate = date('Y-m-d', strtotime($targetDate . ' - 7 days'));

        return Shop::where('status', 'active')
            ->whereHas('invoices', function ($query) use ($lastWeekDate) {
                $query->where('invoice_date', $lastWeekDate)
                    ->where('invoice_type', 'daily')
                    ->whereNull('deleted_at');
            })
            ->whereDoesntHave('invoices', function ($query) use ($targetDate) {
                $query->where('invoice_date', $targetDate)
                    ->where('invoice_type', 'daily')
                    ->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
