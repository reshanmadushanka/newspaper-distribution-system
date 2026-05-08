<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Services\InvoiceService;
use App\Domain\Invoices\Data\InvoiceData;
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

    public function index(): Response
    {
        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $this->invoiceService->getPaginatedInvoices(),
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

        $existing = Invoice::where('invoice_date', $validated['invoice_date'])
            ->where('shop_id', $validated['shop_id'])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'invoice_date' => 'An invoice already exists for this shop on this date.',
            ]);
        }

        $validated['items'] = collect($validated['items'])->map(function ($item) {
            return [
                'newspaper_id' => (int) $item['newspaper_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ];
        })->toArray();

        $invoice = $this->invoiceService->createInvoice($validated, Auth::id());

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(int $id): Response
    {
        $invoice = $this->invoiceService->getInvoiceForView($id);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
        ]);
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

}
