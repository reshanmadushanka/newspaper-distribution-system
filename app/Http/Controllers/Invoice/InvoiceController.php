<?php

namespace App\Http\Controllers\Invoice;

use App\Domain\Invoices\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Domain\Invoices\Services\InvoiceConfirmationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private InvoiceConfirmationService $confirmationService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'date_from', 'date_to', 'shop_id']);
        
        $invoices = $this->invoices->getByDispatchDate(
            $request->get('dispatch_date', now()->addDay()->toDateString())
        );

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices->load(['shop', 'items']),
            'filters' => $filters,
        ]);
    }

    public function show(Invoice $invoice)
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['items.newspaper', 'shop', 'deliveries']),
        ]);
    }

    public function confirm(Request $request, Invoice $invoice)
    {
        try {
            $invoice = $this->confirmationService->confirm(
                $invoice->id,
                auth()->id()
            );

            return response()->json([
                'invoice' => $invoice->load(['items', 'shop']),
                'message' => 'Invoice confirmed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        try {
            $invoice = $this->confirmationService->cancel(
                $invoice->id,
                auth()->id()
            );

            return response()->json([
                'invoice' => $invoice,
                'message' => 'Invoice cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function print(Invoice $invoice)
    {
        $invoice->update(['printed_at' => now()]);

        // Record delivery
        $invoice->deliveries()->create([
            'channel' => 'print',
            'status' => 'delivered',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);

        return response()->json([
            'invoice' => $invoice->load(['items', 'shop']),
            'message' => 'Invoice marked as printed',
        ]);
    }
}
