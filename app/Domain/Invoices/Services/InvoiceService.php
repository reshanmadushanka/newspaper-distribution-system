<?php

namespace App\Domain\Invoices\Services;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
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
        ];

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'newspaper_id' => $item['newspaper_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ];
        }

        return $this->invoiceRepository->createWithItems($invoiceData, $items);
    }

    public function getInvoiceForView(int $id): Invoice
    {
        return $this->invoiceRepository->findOrFail($id);
    }

    public function getActiveNewspapers(): Collection
    {
        return Newspaper::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
    }

    public function getPreviousWeekSummary(string $date, int $shopId): ?Invoice
    {
        $previousWeekDate = date('Y-m-d', strtotime($date . ' - 7 days'));
        return $this->invoiceRepository->findByDateAndShop($previousWeekDate, $shopId);
    }
}
