<?php

namespace App\Jobs;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Shops\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceFromLastWeek implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $shopId,
        public string $targetDate,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastWeekDate = date('Y-m-d', strtotime($this->targetDate . ' - 7 days'));

        // Find last week's invoice for this shop
        $lastWeekInvoice = Invoice::with(['items'])
            ->where('invoice_date', $lastWeekDate)
            ->where('shop_id', $this->shopId)
            ->where('invoice_type', 'daily')
            ->first();

        if (!$lastWeekInvoice) {
            // No invoice from last week, skip
            $this->updateProgress('skipped');
            return;
        }

        // Check if invoice already exists for target date
        $existingInvoice = Invoice::where('invoice_date', $this->targetDate)
            ->where('shop_id', $this->shopId)
            ->where('invoice_type', 'daily')
            ->first();

        if ($existingInvoice) {
            // Invoice already exists, skip
            $this->updateProgress('skipped');
            return;
        }

        // Create new invoice based on last week's data
        DB::transaction(function () use ($lastWeekInvoice) {
            // Create invoice
            $newInvoice = Invoice::create([
                'invoice_date' => $this->targetDate,
                'shop_id' => $this->shopId,
                'created_by' => $this->userId,
                'total_amount' => 0,
                'status' => 'draft',
                'notes' => $lastWeekInvoice->notes,
                'invoice_type' => 'daily',
            ]);

            // Create invoice items
            $invoiceItems = [];
            foreach ($lastWeekInvoice->items as $item) {
                $returnQuantity = $item->return_quantity ?? 0;
                $invoiceItems[] = [
                    'invoice_id' => $newInvoice->id,
                    'newspaper_id' => $item->newspaper_id,
                    'price_id' => $item->price_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->quantity * $item->unit_price,
                    'return_quantity' => $returnQuantity,
                    'return_total_price' => $returnQuantity * $item->unit_price,
                ];
            }

            if (!empty($invoiceItems)) {
                $newInvoice->items()->insert($invoiceItems);

                // Update total amounts
                $totalAmount = array_sum(array_column($invoiceItems, 'total_price'));
                $totalReturnAmount = array_sum(array_column($invoiceItems, 'return_total_price'));
                $newInvoice->update([
                    'total_amount' => $totalAmount,
                    'total_net_amount' => $totalAmount - $totalReturnAmount,
                    'return_total_amount' => $totalReturnAmount
                ]);
            }

            $this->updateProgress('created', $newInvoice->id);
        });
    }

    /**
     * Update progress in cache for real-time tracking
     */
    protected function updateProgress(string $status, ?int $invoiceId = null): void
    {
        $cacheKey = "invoice_generation_{$this->userId}";
        $progress = Cache::get($cacheKey, [
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'invoices' => [],
            'status' => 'processing',
        ]);

        $progress['processed']++;

        if ($status === 'created') {
            $progress['created']++;
            $progress['invoices'][] = [
                'shop_id' => $this->shopId,
                'invoice_id' => $invoiceId,
                'status' => 'created',
            ];
        } else if ($status === 'skipped') {
            $progress['skipped']++;
            $progress['invoices'][] = [
                'shop_id' => $this->shopId,
                'status' => 'skipped',
            ];
        }

        // Check if all jobs are done
        if ($progress['processed'] >= $progress['total']) {
            $progress['status'] = 'completed';
            $progress['completed_at'] = now()->toDateTimeString();
        }

        Cache::put($cacheKey, $progress, now()->addMinutes(30));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $cacheKey = "invoice_generation_{$this->userId}";
        $progress = Cache::get($cacheKey, [
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'invoices' => [],
            'status' => 'processing',
        ]);

        $progress['processed']++;
        $progress['failed']++;
        $progress['invoices'][] = [
            'shop_id' => $this->shopId,
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ];

        if ($progress['processed'] >= $progress['total']) {
            $progress['status'] = 'completed';
            $progress['completed_at'] = now()->toDateTimeString();
        }

        Cache::put($cacheKey, $progress, now()->addMinutes(30));

        Log::error("Failed to generate invoice for shop {$this->shopId}: " . $exception->getMessage());
    }
}
