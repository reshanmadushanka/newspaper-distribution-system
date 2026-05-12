<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator;

    public function find(int $id): ?Invoice;

    public function findOrFail(int $id): Invoice;

    public function createWithItems(array $invoiceData, array $items): Invoice;

    public function findByDateAndShop(string $date, int $shopId): ?Invoice;

    public function updateStatus(int $id, string $status): Invoice;

    public function updateWithItems(int $id, array $invoiceData, array $items): Invoice;

    public function delete(int $id): bool;

    public function getDailyReportInvoices(string $date): Collection;

    public function existsByDateAndShop(string $date, int $shopId, ?int $excludeId = null): bool;
}
