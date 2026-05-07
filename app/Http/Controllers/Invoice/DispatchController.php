<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Domain\Forecasting\Services\ForecastingService;
use App\Domain\Invoices\Services\InvoiceGenerationService;
use App\Domain\Invoices\Services\InvoiceConfirmationService;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DispatchController extends Controller
{
    public function __construct(
        private ForecastingService $forecasting,
        private InvoiceGenerationService $invoiceGeneration,
        private InvoiceConfirmationService $invoiceConfirmation,
        private InvoiceRepositoryInterface $invoices
    ) {}

    public function create(Request $request)
    {
        $dispatchDate = $request->get('dispatch_date', now()->addDay()->toDateString());
        
        return Inertia::render('Invoices/Dispatch/Create', [
            'dispatchDate' => $dispatchDate,
            'shops' => \App\Domain\Shops\Models\Shop::active()->get(),
            'newspapers' => \App\Domain\Newspapers\Models\Newspaper::active()->get(),
        ]);
    }

    public function forecast(Request $request)
    {
        $validated = $request->validate([
            'dispatch_date' => 'required|date',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $forecasts = $this->forecasting->generateForecast(
            $validated['dispatch_date'],
            $validated['shop_id'] ?? null
        );

        return response()->json([
            'forecasts' => $forecasts->map(function ($forecast) {
                return [
                    'id' => $forecast->id,
                    'shop' => $forecast->shop,
                    'newspaper' => $forecast->newspaper,
                    'suggested_quantity' => $forecast->suggested_quantity,
                    'forecast_date' => $forecast->forecast_date,
                ];
            }),
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'dispatch_date' => 'required|date',
            'shop_id' => 'nullable|exists:shops,id',
            'quantities' => 'nullable|array',
        ]);

        $invoices = $this->invoiceGeneration->generateInvoices(
            $validated['dispatch_date'],
            auth()->id(),
            $validated['shop_id'] ?? null,
            $validated['quantities'] ?? null
        );

        return response()->json([
            'invoices' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'shop_name' => $invoice->shop->name,
                    'dispatch_date' => $invoice->dispatch_date,
                    'status' => $invoice->status,
                    'items' => $invoice->items->map(fn ($item) => [
                        'id' => $item->id,
                        'newspaper_name' => $item->newspaper_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total,
                    ]),
                ];
            }),
            'message' => 'Invoices generated successfully',
        ]);
    }

    public function edit($invoiceId)
    {
        $invoice = \App\Domain\Invoices\Models\Invoice::findOrFail($invoiceId);
        
        if (!$invoice->isDraft()) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['error' => 'Only draft invoices can be edited']);
        }

        return Inertia::render('Invoices/Dispatch/Edit', [
            'invoice' => $invoice->load(['items.newspaper', 'shop']),
        ]);
    }

    public function update(Request $request, $invoiceId)
    {
        $invoice = \App\Domain\Invoices\Models\Invoice::findOrFail($invoiceId);
        
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:invoice_items,id',
                'items.*.quantity' => 'required|integer|min:0',
                'items.*.reason' => 'nullable|string',
            ]);

            $invoice = $this->invoiceGeneration->updateDraftInvoice(
                $invoice,
                $validated['items']
            );

            return response()->json([
                'invoice' => $invoice->load(['items', 'shop']),
                'message' => 'Invoice updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirm($invoiceId)
    {
        try {
            $invoice = $this->invoiceConfirmation->confirm($invoiceId, auth()->id());

            return response()->json([
                'invoice' => $invoice,
                'message' => 'Invoice confirmed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function markAsDispatched($deliveryId)
    {
        $delivery = \App\Domain\Invoices\Models\InvoiceDelivery::findOrFail($deliveryId);

        if ($delivery->status !== \App\Domain\Invoices\Enums\InvoiceDeliveryStatus::PENDING) {
            return response()->json(['error' => 'Delivery cannot be marked as dispatched'], 422);
        }

        $delivery->update([
            'status' => \App\Domain\Invoices\Enums\InvoiceDeliveryStatus::SENT,
            'sent_at' => now(),
        ]);

        return response()->json([
            'delivery' => $delivery,
            'message' => 'Delivery marked as dispatched',
        ]);
    }

    public function markAsDelivered($deliveryId)
    {
        $delivery = \App\Domain\Invoices\Models\InvoiceDelivery::findOrFail($deliveryId);

        if ($delivery->status !== \App\Domain\Invoices\Enums\InvoiceDeliveryStatus::SENT) {
            return response()->json(['error' => 'Delivery must be dispatched first'], 422);
        }

        $delivery->update([
            'status' => \App\Domain\Invoices\Enums\InvoiceDeliveryStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        return response()->json([
            'delivery' => $delivery,
            'message' => 'Delivery marked as delivered',
        ]);
    }
}
