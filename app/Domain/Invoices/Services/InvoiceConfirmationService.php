<?php

namespace App\Domain\Invoices\Services;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Enums\InvoiceStatus;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InvoiceConfirmationService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices
    ) {}

    public function confirm(int $invoiceId, int $userId): Invoice
    {
        return DB::transaction(function () use ($invoiceId, $userId) {
            $invoice = $this->invoices->findWithLock($invoiceId);

            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            if (!$invoice->isDraft()) {
                throw new \Exception('Only draft invoices can be confirmed');
            }

            if ($invoice->items->isEmpty()) {
                throw new \Exception('Cannot confirm an invoice with no items');
            }

            $invoice = $this->invoices->confirm($invoice, $userId);

            // Create delivery record
            \App\Domain\Invoices\Models\InvoiceDelivery::create([
                'invoice_id' => $invoice->id,
                'channel' => \App\Domain\Invoices\Enums\InvoiceDeliveryChannel::PRINT->value,
                'status' => 'pending',
            ]);

            return $invoice->fresh(['items', 'shop']);
        });
    }

    public function cancel(int $invoiceId, int $userId): Invoice
    {
        return DB::transaction(function () use ($invoiceId, $userId) {
            $invoice = $this->invoices->findWithLock($invoiceId);

            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            if ($invoice->status === InvoiceStatus::CANCELLED) {
                throw new \Exception('Invoice is already cancelled');
            }

            if ($invoice->status === InvoiceStatus::REVERSED) {
                throw new \Exception('Cannot cancel a reversed invoice');
            }

            $invoice->update([
                'status' => InvoiceStatus::CANCELLED->value,
            ]);

            return $invoice->fresh();
        });
    }
}
