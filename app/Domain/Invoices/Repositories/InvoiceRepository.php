<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Enums\InvoiceStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function find(int $id): ?Invoice
    {
        return Invoice::with(['items', 'shop'])->find($id);
    }

    public function findWithLock(int $id): ?Invoice
    {
        return Invoice::with(['items', 'shop'])
            ->lockForUpdate()
            ->find($id);
    }

    public function findByInvoiceNo(string $invoiceNo): ?Invoice
    {
        return Invoice::with(['items', 'shop'])
            ->where('invoice_no', $invoiceNo)
            ->first();
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }

    public function confirm(Invoice $invoice, int $userId): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::CONFIRMED,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function getByDispatchDate(string $date): Collection
    {
        return Invoice::with(['items', 'shop'])
            ->where('dispatch_date', $date)
            ->get();
    }

    public function getShopInvoices(int $shopId, array $filters = []): Collection
    {
        $query = Invoice::with(['items'])
            ->where('shop_id', $shopId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('invoice_date', 'desc')->get();
    }
}
