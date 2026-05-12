<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Services\SystemInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminPaymentController extends Controller
{
    public function __construct(
        private SystemInvoiceService $systemInvoiceService
    ) {}

    /**
     * Show admin's pending invoices (dashboard alert view)
     */
    public function pendingInvoices(): Response
    {
        $admin = Auth::user();
        $pendingInvoices = $this->systemInvoiceService->getPendingInvoices($admin);

        return Inertia::render('Admin/Payments/PendingInvoices', [
            'invoices' => $pendingInvoices,
            'stats' => $this->systemInvoiceService->getDashboardStats($admin),
        ]);
    }

    /**
     * View single pending invoice details
     */
    public function viewInvoice(int $id): Response
    {
        $admin = Auth::user();
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        // Verify this invoice belongs to the authenticated admin
        if ($invoice->admin_id !== $admin->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        return Inertia::render('Admin/Payments/InvoiceDetail', [
            'invoice' => $invoice->load(['creator']),
        ]);
    }

    /**
     * Admin marks their own invoice as paid
     */
    public function updatePaymentStatus(int $id, Request $request): RedirectResponse
    {
        $admin = Auth::user();
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        // Verify this invoice belongs to the authenticated admin
        if ($invoice->admin_id !== $admin->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:100',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $this->systemInvoiceService->markAsPaid(
            $invoice,
            $validated['payment_method'],
            $validated['payment_notes'] ?? null
        );

        return redirect()->route('admin.payments.invoice-detail', $invoice->id)
            ->with('success', 'Thank you for your payment!');
    }

    /**
     * Show admin's payment history
     */
    public function paymentHistory(): Response
    {
        $admin = Auth::user();
        $paidInvoices = $this->systemInvoiceService->getPaidInvoices($admin, perPage: 20);

        return Inertia::render('Admin/Payments/History', [
            'invoices' => $paidInvoices,
            'stats' => $this->systemInvoiceService->getDashboardStats($admin),
        ]);
    }

    /**
     * Download payment invoice PDF
     */
    public function downloadInvoicePdf(int $id)
    {
        $admin = Auth::user();
        $invoice = $this->systemInvoiceService->getInvoiceById($id);

        // Verify this invoice belongs to the authenticated admin
        if ($invoice->admin_id !== $admin->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        // For now, we'll return a simple response - you can implement PDF generation using dompdf
        // This would be implemented based on your PDF setup
        return response()->json(['message' => 'PDF download feature coming soon']);
    }

    /**
     * Get dashboard alert data
     */
    public function getDashboardAlert(): Response|array
    {
        $admin = Auth::user();
        $pendingCount = $this->systemInvoiceService->getPendingInvoicesCount($admin);
        $pendingInvoices = $this->systemInvoiceService->getPendingInvoicesForDashboard($admin);

        return [
            'pending_count' => $pendingCount,
            'pending_invoices' => $pendingInvoices,
        ];
    }
}
