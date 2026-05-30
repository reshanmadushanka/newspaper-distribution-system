<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Invoices\Services\InvoiceService;
use App\Domain\Invoices\Data\InvoiceData;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Shops\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function index(Request $request): Response
    {
        $defaultDateFrom = today()->subDays(6)->toDateString();
        $defaultDateTo = today()->toDateString();

        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'invoice_type' => 'nullable|string|in:daily,monthly',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $dateFrom = $validated['date_from'] ?? $defaultDateFrom;
        $dateTo = $validated['date_to'] ?? $defaultDateTo;
        $invoiceType = $validated['invoice_type'] ?? null;

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $this->invoiceService->getPaginatedInvoices(
                search: $search,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                invoiceType: $invoiceType
            ),
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'invoice_type' => $invoiceType,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $previousWeekSummary = null;
        if ($request->has(['date', 'shop_id'])) {
            $previousWeekSummary = $this->invoiceService->getPreviousWeekSummary(
                $request->date,
                (int) $request->shop_id
            );
        }

        return Inertia::render('Admin/Invoices/Form', [
            'invoice' => null,
            'shops' => Shop::query()->where('status', 'active')->orderBy('name')->get(),
            'newspapers' => $this->invoiceService->getActiveNewspapers(),
            'previousWeekSummary' => $previousWeekSummary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(InvoiceData::rules());

        $existing = $this->invoiceService->checkInvoiceExistsForDateAndShop($validated['invoice_date'], $validated['shop_id']);

        if ($existing) {
            throw ValidationException::withMessages([
                'invoice_date' => 'An invoice already exists for this shop on this date.',
            ]);
        }

        $validated['items'] = collect($validated['items'])->map(function ($item) {
            return [
                'newspaper_id' => (int) $item['newspaper_id'],
                'price_id' => (int) $item['price_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'return_quantity' => isset($item['return_quantity']) ? (int) $item['return_quantity'] : 0,
            ];
        })->toArray();

        $invoice = $this->invoiceService->createInvoice($validated, Auth::id());

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(int $id)
    {
        $invoice = $this->invoiceService->getInvoiceForView($id);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(int $id)
    {
        $invoice = $this->invoiceService->getInvoiceForEdit($id);

        return Inertia::render('Admin/Invoices/Form', [
            'invoice' => $invoice,
            'shops' => Shop::query()->where('status', 'active')->orderBy('name')->get(),
            'newspapers' => $this->invoiceService->getActiveNewspapers(),
            'previousWeekSummary' => null,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.newspaper_id' => 'required|exists:newspapers,id',
            'items.*.price_id' => 'required|exists:newspaper_prices,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.return_quantity' => 'nullable|integer|min:0',
            'invoice_type' => 'nullable|string|in:daily,monthly',
            'previous_deficit' => 'nullable|numeric|min:0',
            'special_discount' => 'nullable|numeric|min:0',
        ]);

        $invoice = $this->invoiceService->getInvoiceForEdit($id);
        $existing = $this->invoiceService->checkInvoiceExistsForDateAndShop(
            $validated['invoice_date'],
            $invoice->shop_id,
            $id
        );

        if ($existing) {
            throw ValidationException::withMessages([
                'invoice_date' => 'An invoice already exists for this shop on this date.',
            ]);
        }

        $validated['items'] = collect($validated['items'])->map(function ($item) {
            return [
                'newspaper_id' => (int) $item['newspaper_id'],
                'price_id' => (int) $item['price_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'return_quantity' => isset($item['return_quantity']) ? (int) $item['return_quantity'] : 0,
            ];
        })->toArray();

        $this->invoiceService->updateInvoice($id, $validated);

        return redirect()->route('admin.invoices.show', $id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->invoiceService->deleteInvoice($id);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function markAsPaid(int $id): RedirectResponse
    {
        $this->invoiceService->markAsPaid($id);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice marked as paid.');
    }

    public function markAsPrinted(int $id)
    {
        $invoice = $this->invoiceService->markAsPrinted($id);

        // return response()->json([
        //     'message' => 'Invoice marked as printed.',
        //     'printed_at' => $invoice->printed_at,
        // ]);
    }

    public function dailySales(Request $request)
    {
        $defaultDateFrom = today()->subDays(6)->toDateString();
        $defaultDateTo = today()->toDateString();

        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'shop_id' => 'nullable|integer|exists:shops,id',
            'newspaper_id' => 'nullable|integer|exists:newspapers,id',
            'show_profit' => 'nullable|boolean',
            'invoice_type' => 'nullable|string|in:daily,monthly',
        ]);

        $dateFrom = $validated['date_from'] ?? $defaultDateFrom;
        $dateTo = $validated['date_to'] ?? $defaultDateTo;
        $shopId = $validated['shop_id'] ?? null;
        $newspaperId = $validated['newspaper_id'] ?? null;
        $showProfit = $request->has('show_profit') ? $request->boolean('show_profit') : true;
        $invoiceType = $validated['invoice_type'] ?? null;

        return Inertia::render('Admin/Reports/DailySales', [
            'shopReport' => $this->invoiceService->getByShopReport($dateFrom, $dateTo, $shopId, $invoiceType),
            'newspaperReport' => $this->invoiceService->getByNewspaperReport($dateFrom, $dateTo, $newspaperId, $invoiceType),
            'invoiceReport' => $this->invoiceService->getInvoiceListReport($dateFrom, $dateTo, $shopId, $newspaperId, $invoiceType),
            'shops' => Shop::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'newspapers' => Newspaper::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'shop_id' => $shopId,
                'newspaper_id' => $newspaperId,
                'show_profit' => $showProfit,
                'invoice_type' => $invoiceType,
            ],
        ]);
    }

    public function dailySalesPdf(Request $request): \Illuminate\Http\Response
    {
        $defaultDateFrom = today()->subDays(6)->toDateString();
        $defaultDateTo = today()->toDateString();

        $validated = $request->validate([
            'report_type' => 'nullable|in:by-shop,by-newspaper,by-invoice',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'shop_id' => 'nullable|integer|exists:shops,id',
            'newspaper_id' => 'nullable|integer|exists:newspapers,id',
            'show_profit' => 'nullable|boolean',
            'invoice_type' => 'nullable|string|in:daily,monthly',
        ]);

        $reportType = $validated['report_type'] ?? 'by-shop';
        $dateFrom = $validated['date_from'] ?? $defaultDateFrom;
        $dateTo = $validated['date_to'] ?? $defaultDateTo;
        $shopId = $validated['shop_id'] ?? null;
        $newspaperId = $validated['newspaper_id'] ?? null;
        $showProfit = $request->has('show_profit') ? $request->boolean('show_profit') : true;
        $invoiceType = $validated['invoice_type'] ?? null;

        $report = match ($reportType) {
            'by-newspaper' => $this->invoiceService->getByNewspaperReport($dateFrom, $dateTo, $newspaperId),
            'by-invoice' => $this->invoiceService->getInvoiceListReport($dateFrom, $dateTo, $shopId, $newspaperId, $invoiceType),
            default => $this->invoiceService->getByShopReport($dateFrom, $dateTo, $shopId),
        };

        $filters = [
            'shop' => $shopId ? Shop::query()->find($shopId, ['name'])?->name : 'All Shops',
            'newspaper' => $newspaperId ? Newspaper::query()->find($newspaperId, ['name'])?->name : 'All Newspapers',
        ];

        $pdf = Pdf::loadView('pdf.daily-sales', [
            'report' => $report,
            'reportType' => $reportType,
            'filters' => $filters,
            'showProfit' => $showProfit,
        ]);

        return $pdf->download("daily-sales-{$reportType}-{$dateFrom}-to-{$dateTo}.pdf");
    }

    public function downloadPdf(int $id): \Illuminate\Http\Response
    {
        $invoice = $this->invoiceService->getInvoiceForView($id);
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->download("invoice-{$id}.pdf");
    }

    public function streamPdf(int $id): \Illuminate\Http\Response
    {
        $invoice = $this->invoiceService->getInvoiceForView($id);
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->stream("invoice-{$id}.pdf");
    }

    public function autoGeneratePreview(Request $request): \Inertia\Response
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $targetDate = $validated['date'];
        $lastWeekDate = date('Y-m-d', strtotime($targetDate . ' - 7 days'));

        $shopsWithLastWeekInvoices = $this->invoiceService->getShopsWithLastWeekInvoicesButNotForDate($targetDate);

        return Inertia::render('Admin/Invoices/Index', [
            'previewData' => [
                'target_date' => $targetDate,
                'last_week_date' => $lastWeekDate,
                'eligible_shops_count' => $shopsWithLastWeekInvoices->count(),
                'shops' => $shopsWithLastWeekInvoices,
            ],
        ]);
    }

    public function autoGenerate(Request $request): \Inertia\Response
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $targetDate = $validated['date'];
        $userId = Auth::id();

        $result = $this->invoiceService->dispatchInvoiceGeneration($targetDate, $userId);

        return Inertia::render('Admin/Invoices/Index', [
            'generationResult' => [
                'message' => $result['message'],
                'total_shops' => $result['total_shops'],
                'target_date' => $result['target_date'],
            ],
        ]);
    }

    public function autoGenerateProgress(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::id();
        $progress = $this->invoiceService->getGenerationProgress($userId);

        return response()->json($progress);
    }

    public function autoGenerateClear(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::id();
        $this->invoiceService->clearGenerationProgress($userId);

        return response()->json([
            'message' => 'Progress cleared',
        ]);
    }

    public function deleteItem(Invoice $invoice, InvoiceItem $item)
    {
        try {
            $updated = $this->invoiceService->deleteInvoiceItem($invoice->id, $item->id);

            // Redirect back to the invoice edit page so Inertia handles the client-side visit
            return redirect()->route('admin.invoices.edit', $invoice->id)
                ->with('success', 'Item deleted successfully.')
                ->setStatusCode(303);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to delete item'], 500);
        }
    }

}
