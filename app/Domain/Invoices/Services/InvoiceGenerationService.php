<?php

namespace App\Domain\Invoices\Services;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Shops\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceGenerationService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices
    ) {}

    public function generateInvoices(
        string $dispatchDate,
        int $preparedBy,
        ?int $shopId = null,
        ?array $quantities = null
    ): Collection {
        return DB::transaction(function () use ($dispatchDate, $preparedBy, $shopId, $quantities) {
            $shops = $shopId
                ? Shop::where('id', $shopId)->where('status', 'active')->get()
                : Shop::where('status', 'active')->get();

            $invoices = collect();

            foreach ($shops as $shop) {
                $invoice = $this->generateShopInvoice(
                    $shop,
                    $dispatchDate,
                    $preparedBy,
                    $quantities[$shop->id] ?? null
                );

                if ($invoice) {
                    $invoices->push($invoice);
                }
            }

            return $invoices;
        });
    }

    private function generateShopInvoice(
        Shop $shop,
        string $dispatchDate,
        int $preparedBy,
        ?array $customQuantities = null
    ): ?Invoice {
        $newspapers = Newspaper::active()->get();
        
        if ($newspapers->isEmpty()) {
            return null;
        }

        $invoice = $this->invoices->create([
            'invoice_no' => $this->generateInvoiceNumber(),
            'shop_id' => $shop->id,
            'route_id' => null,
            'invoice_date' => now()->toDateString(),
            'dispatch_date' => $dispatchDate,
            'status' => 'draft',
            'gross_total' => 0,
            'net_total' => 0,
            'balance_total' => 0,
            'prepared_by' => $preparedBy,
        ]);

        $grossTotal = 0;

        foreach ($newspapers as $newspaper) {
            $quantity = $customQuantities[$newspaper->id] ?? $this->getForecastQuantity(
                $shop->id,
                $newspaper->id,
                $dispatchDate
            );

            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = $this->getEffectivePrice($newspaper, $dispatchDate);
            
            if ($unitPrice === null) {
                Log::warning("No effective price found for newspaper {$newspaper->id} on {$dispatchDate}");
                continue;
            }

            $lineTotal = $quantity * $unitPrice;
            $grossTotal += $lineTotal;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'newspaper_id' => $newspaper->id,
                'newspaper_code' => $newspaper->code ?? '',
                'newspaper_name' => $newspaper->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'forecast_quantity' => $quantity,
                'manual_adjustment_reason' => null,
            ]);
        }

        // Reload invoice to get items
        $invoice = $invoice->fresh(['items']);

        if ($invoice->items->isEmpty()) {
            $invoice->delete();
            return null;
        }

        $invoice->update([
            'gross_total' => $grossTotal,
            'net_total' => $grossTotal,
            'balance_total' => $grossTotal,
        ]);

        return $invoice->fresh(['items']);
    }

    private function getForecastQuantity(int $shopId, int $newspaperId, string $dispatchDate): int
    {
        $forecast = DB::table('dispatch_forecasts')
            ->where('shop_id', $shopId)
            ->where('newspaper_id', $newspaperId)
            ->where('forecast_date', $dispatchDate)
            ->first();

        return $forecast ? ($forecast->final_quantity ?? $forecast->suggested_quantity) : 0;
    }

    private function getEffectivePrice(Newspaper $newspaper, string $date): ?float
    {
        $priceRecord = DB::table('newspaper_prices')
            ->where('newspaper_id', $newspaper->id)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();

        if ($priceRecord) {
            return $priceRecord->price;
        }

        return $newspaper->price;
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $lastInvoice = Invoice::where('invoice_no', 'like', "INV-{$date}-%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_no, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "INV-{$date}-{$newNumber}";
    }

    public function updateDraftInvoice(Invoice $invoice, array $items): Invoice
    {
        if (!$invoice->isDraft()) {
            throw new \Exception('Cannot update a non-draft invoice');
        }

        return DB::transaction(function () use ($invoice, $items) {
            $grossTotal = 0;

            foreach ($items as $itemData) {
                $item = InvoiceItem::where('id', $itemData['id'])
                    ->where('invoice_id', $invoice->id)
                    ->first();

                if ($item) {
                    $quantity = $itemData['quantity'];
                    $lineTotal = $quantity * $item->unit_price;
                    $grossTotal += $lineTotal;

                    $item->update([
                        'quantity' => $quantity,
                        'line_total' => $lineTotal,
                        'manual_adjustment_reason' => $itemData['reason'] ?? null,
                    ]);
                }
            }

            $invoice->update([
                'gross_total' => $grossTotal,
                'net_total' => $grossTotal,
                'balance_total' => $grossTotal,
            ]);

            return $invoice->fresh(['items']);
        });
    }
}
