<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    public function find(int $id): ?Invoice;
    public function findWithLock(int $id): ?Invoice;
    public function findByInvoiceNo(string $invoiceNo): ?Invoice;
    public function create(array $data): Invoice;
    public function update(Invoice $invoice, array $data): Invoice;
    public function confirm(Invoice $invoice, int $userId): Invoice;
    public function getByDispatchDate(string $date): Collection;
    public function getShopInvoices(int $shopId, array $filters = []): Collection;
}
