<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Services\SystemInvoiceService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SystemInvoiceController extends Controller
{
    public function __construct(
        private SystemInvoiceService $systemInvoiceService
    ) {}

    /**
     * Display list of system invoices created by super admin
     */
    public function index(Request $request): Response
    {
        $invoices = $this->systemInvoiceService->getCreatedInvoices(
            Auth::user(),
            perPage: 15
        );

        return Inertia::render('Admin/SystemInvoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Show create system invoice form
     */
    public function create(): Response
    {
        // Get all users with admin role
        $admins = User::role('admin')->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Admin/SystemInvoices/Create', [
            'admins' => $admins,
        ]);
    }

    /**
     * Store new system invoice
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_id' => 'required|exists:users,id|integer',
            'reason' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'nullable|string|max:1000',
            'bank_account_details' => 'nullable|string|max:1000',
        ]);

        $invoice = $this->systemInvoiceService->createInvoice(
            admin: User::findOrFail($validated['admin_id']),
            createdBy: Auth::user(),
            reason: $validated['reason'],
            amount: $validated['amount'],
            description: $validated['description'] ?? null,
            bankAccountDetails: $validated['bank_account_details'] ?? null
        );

        return redirect()->route('admin.system-invoices.show', $invoice->id)
            ->with('success', 'Invoice created and sent to admin successfully.');
    }

    /**
     * Show system invoice details
     */
    public function show(int $id): Response
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        return Inertia::render('Admin/SystemInvoices/Show', [
            'invoice' => $invoice->load(['admin', 'creator']),
        ]);
    }

    /**
     * Show edit system invoice form
     */
    public function edit(int $id): Response
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        $admins = User::role('admin')->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Admin/SystemInvoices/Edit', [
            'invoice' => $invoice,
            'admins' => $admins,
        ]);
    }

    /**
     * Update system invoice
     */
    public function update(int $id, Request $request): RedirectResponse
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        $validated = $request->validate([
            'admin_id' => 'required|exists:users,id|integer',
            'reason' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'description' => 'nullable|string|max:1000',
            'bank_account_details' => 'nullable|string|max:1000',
        ]);

        $this->systemInvoiceService->updateInvoice($invoice, $validated);

        return redirect()->route('admin.system-invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Delete system invoice
     */
    public function destroy(int $id): RedirectResponse
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        $this->systemInvoiceService->deleteInvoice($invoice);

        return redirect()->route('admin.system-invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Mark invoice as paid (for admin payment update)
     */
    public function markAsPaid(int $id, Request $request): RedirectResponse
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:100',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $this->systemInvoiceService->markAsPaid(
            $invoice,
            $validated['payment_method'] ?? null,
            $validated['payment_notes'] ?? null
        );

        return redirect()->route('admin.system-invoices.show', $invoice->id)
            ->with('success', 'Invoice marked as paid successfully.');
    }

    /**
     * Mark invoice as pending
     */
    public function markAsPending(int $id): RedirectResponse
    {
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        $this->systemInvoiceService->markAsPending($invoice);

        return redirect()->route('admin.system-invoices.show', $invoice->id)
            ->with('success', 'Invoice marked as pending successfully.');
    }
}
