<?php

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Repositories\SystemInvoiceRepository;
use App\Domain\Admin\Enums\SystemInvoiceStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SystemInvoiceService
{
    public function __construct(
        private SystemInvoiceRepository $repository
    ) {}

    /**
     * Get all pending invoices for an admin with pagination
     */
    public function getPendingInvoices(User $admin, int $perPage = 15)
    {
        return $this->repository->getPaginatedPendingForAdmin($admin, $perPage);
    }

    /**
     * Get all paid invoices for an admin with pagination
     */
    public function getPaidInvoices(User $admin, int $perPage = 15)
    {
        return $this->repository->getPaginatedPaidForAdmin($admin, $perPage);
    }

    /**
     * Get all invoices for an admin with pagination
     */
    public function getAdminInvoices(User $admin, int $perPage = 15)
    {
        return $this->repository->getPaginatedForAdmin($admin, $perPage);
    }

    /**
     * Get invoices created by super admin
     */
    public function getCreatedInvoices(User $createdBy, int $perPage = 15)
    {
        return $this->repository->getPaginatedCreatedByAdmin($createdBy, $perPage);
    }

    /**
     * Get pending invoices count for admin (for dashboard alert)
     */
    public function getPendingInvoicesCount(User $admin): int
    {
        return $this->repository->getPendingForAdmin($admin)->count();
    }

    /**
     * Get pending invoices for admin (for dashboard alert)
     */
    public function getPendingInvoicesForDashboard(User $admin)
    {
        return $this->repository->getPendingForAdmin($admin)
            ->map(function (SystemInvoice $invoice) {
                return [
                    'id' => $invoice->id,
                    'reason' => $invoice->reason,
                    'amount' => $invoice->amount,
                    'created_at' => $invoice->created_at,
                    'creator_name' => $invoice->creator->name,
                ];
            });
    }

    /**
     * Get system invoice by ID
     */
    public function getInvoiceById(int $id): SystemInvoice
    {
        return $this->repository->getById($id);
    }

    /**
     * Create a new system invoice
     */
    public function createInvoice(
        User $admin,
        User $createdBy,
        string $reason,
        float $amount,
        ?string $description = null,
        ?string $bankAccountDetails = null
    ): SystemInvoice
    {
        return DB::transaction(function () use ($admin, $createdBy, $reason, $amount, $description, $bankAccountDetails) {
            return $this->repository->create([
                'admin_id' => $admin->id,
                'created_by' => $createdBy->id,
                'reason' => $reason,
                'amount' => $amount,
                'description' => $description,
                'bank_account_details' => $bankAccountDetails,
                'status' => SystemInvoiceStatus::PENDING->value,
            ]);
        });
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(
        SystemInvoice $invoice,
        ?string $paymentMethod = null,
        ?string $paymentNotes = null
    ): SystemInvoice
    {
        return DB::transaction(function () use ($invoice, $paymentMethod, $paymentNotes) {
            $invoice->markAsPaid($paymentMethod, $paymentNotes);
            return $invoice;
        });
    }

    /**
     * Mark invoice as pending
     */
    public function markAsPending(SystemInvoice $invoice): SystemInvoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => SystemInvoiceStatus::PENDING->value,
                'paid_at' => null,
                'payment_method' => null,
                'payment_notes' => null,
            ]);
            return $invoice;
        });
    }

    /**
     * Update invoice details
     */
    public function updateInvoice(SystemInvoice $invoice, array $data): SystemInvoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $this->repository->update($invoice, $data);
            return $invoice->refresh();
        });
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice(SystemInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            return $this->repository->delete($invoice);
        });
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(User $user)
    {
        return $this->repository->getDashboardStats($user);
    }

    /**
     * Get payment greeting message
     */
    public function getPaymentGreetingMessage(SystemInvoice $invoice): string
    {
        return "Thank you for your payment of Rs. " . number_format($invoice->amount, 2) . " for " . $invoice->reason;
    }
}
