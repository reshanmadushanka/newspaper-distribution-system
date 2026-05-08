<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Invoices\Services\InvoiceService;
use App\Domain\Invoices\Data\InvoiceData;
use App\Domain\Shops\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function create(): Response
    {
        return Inertia::render('Admin/Invoices/Form', [
            'invoice' => null,
            'shops' => Shop::query()->where('status', 'active')->orderBy('name')->get(),
            'newspapers' => $this->invoiceService->getActiveNewspapers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(InvoiceData::rules());

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
}
