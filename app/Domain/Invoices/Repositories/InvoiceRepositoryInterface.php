<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $invoiceType = null,
        ?int $newspaperId = null
    ): LengthAwarePaginator;

    public function find(int $id): ?Invoice;

    public function findOrFail(int $id): Invoice;

    public function createWithItems(array $invoiceData, array $items): Invoice;

    public function findByDateAndShop(string $date, int $shopId): ?Invoice;

    public function updateStatus(int $id, string $status): Invoice;

    public function markAsPrinted(int $id): Invoice;

    public function updateWithItems(int $id, array $invoiceData, array $items): Invoice;

    public function delete(int $id): bool;

    public function getDailyReportInvoices(string $date): Collection;

    public function getByShopReport(string $dateFrom, string $dateTo, ?int $shopId = null): Collection;

    public function getByNewspaperReport(string $dateFrom, string $dateTo, ?int $newspaperId = null, ?int $shopId = null, ?string $invoiceType = null): Collection;

    public function getInvoiceList(
        string $dateFrom,
        string $dateTo,
        ?int $shopId = null,
        ?int $newspaperId = null,
        ?string $invoiceType = null
    ): Collection;

    public function existsByDateAndShop(string $date, int $shopId, ?int $excludeId = null): bool;

    public function getShopsWithoutInvoicesForDate(string $date): Collection;

    public function getShopsWithLastWeekInvoicesButNotForDate(string $targetDate): Collection;

        // Invoice item specific operations
        public function findItem(int $id): ?InvoiceItem;

        public function deleteItem(int $id): bool;

        public function recalculateTotals(int $invoiceId): Invoice;
}
