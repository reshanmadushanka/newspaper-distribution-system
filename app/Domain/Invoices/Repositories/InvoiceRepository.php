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
        ?string $dateTo = null
    ): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $invoiceId = ltrim($search, '#');

        return Invoice::query()
            ->with('shop')
            ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo]);
            })
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
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
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
            ], $items);

            $invoice->items()->insert($invoiceItems);

            $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
            $invoice->update(['total_amount' => $totalAmount]);

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

    public function updateWithItems(int $id, array $invoiceData, array $items): Invoice
    {
        return DB::transaction(function () use ($id, $invoiceData, $items) {
            $invoice = $this->findOrFail($id);
            $invoice->update($invoiceData);

            $invoice->items()->delete();

            $invoiceItems = array_map(fn($item) => [
                'invoice_id'   => $invoice->id,
                'newspaper_id' => $item['newspaper_id'],
                'price_id'     => $item['price_id'] ?? null,
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'total_price'  => $item['quantity'] * $item['unit_price'],
            ], $items);

            $invoice->items()->insert($invoiceItems);

            $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice->fresh(['shop', 'items.newspaper', 'items.price', 'creator']);
        });
    }

    public function delete(int $id): bool
    {
        return Invoice::findOrFail($id)->delete();
    }

    public function getDailyReportInvoices(string $date): Collection
    {
        return Invoice::with(['shop', 'items.newspaper'])
            ->where('invoice_date', $date)
            ->orderBy('shop_id')
            ->get();
    }

    public function getByShopReport(string $dateFrom, string $dateTo, ?int $shopId = null): Collection
    {
        return Invoice::with(['shop', 'items.newspaper'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('shop_id')
            ->orderBy('invoice_date')
            ->get();
    }

    public function getByNewspaperReport(string $dateFrom, string $dateTo, ?int $newspaperId = null): Collection
    {
        return InvoiceItem::with(['invoice.shop', 'newspaper'])
            ->whereHas('invoice', fn($q) => $q->whereBetween('invoice_date', [$dateFrom, $dateTo]))
            ->when($newspaperId, fn($q) => $q->where('newspaper_id', $newspaperId))
            ->get();
    }

    public function getInvoiceList(
        string $dateFrom,
        string $dateTo,
        ?int $shopId = null,
        ?int $newspaperId = null
    ): Collection
    {
        return Invoice::with(['shop', 'items'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
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
                    ->whereNull('deleted_at');
            })
            ->whereDoesntHave('invoices', function ($query) use ($targetDate) {
                $query->where('invoice_date', $targetDate)
                    ->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
