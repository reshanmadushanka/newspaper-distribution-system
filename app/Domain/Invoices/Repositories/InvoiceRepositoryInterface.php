<?php

namespace App\Domain\Invoices\Repositories;

use App\Domain\Invoices\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function find(int $id): ?Invoice;

    public function findOrFail(int $id): Invoice;

    public function createWithItems(array $invoiceData, array $items): Invoice;
}
