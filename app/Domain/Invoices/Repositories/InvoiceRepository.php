<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['shop', 'items.newspaper'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::with(['shop', 'items.newspaper', 'creator'])->find($id);
    }

    public function findOrFail(int $id): Invoice
    {
        return Invoice::with(['shop', 'items.newspaper', 'creator'])->findOrFail($id);
    }

    public function createWithItems(array $invoiceData, array $items): Invoice
    {
        return DB::transaction(function () use ($invoiceData, $items) {
            $invoice = Invoice::create($invoiceData);

            $invoiceItems = array_map(fn($item) => [
                'invoice_id'   => $invoice->id,
                'newspaper_id' => $item['newspaper_id'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'total_price'  => $item['quantity'] * $item['unit_price'],
            ], $items);

            $invoice->items()->insert($invoiceItems);

            $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice->fresh(['shop', 'items.newspaper', 'creator']);
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
        return $invoice->fresh(['shop', 'items.newspaper', 'creator']);
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
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'total_price'  => $item['quantity'] * $item['unit_price'],
            ], $items);

            $invoice->items()->insert($invoiceItems);

            $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice->fresh(['shop', 'items.newspaper', 'creator']);
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
}
