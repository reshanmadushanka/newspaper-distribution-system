<?php

namespace App\Domain\Admin\Repositories;

use App\Domain\Admin\Models\SystemInvoice;
use App\Domain\Admin\Enums\SystemInvoiceStatus;
use App\Models\User;

class SystemInvoiceRepository
{
    /**
     * Get paginated system invoices for admin
     */
    public function getPaginatedForAdmin(User $admin, int $perPage = 15)
    {
        return SystemInvoice::forAdmin($admin)
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated pending invoices for admin
     */
    public function getPaginatedPendingForAdmin(User $admin, int $perPage = 15)
    {
        return SystemInvoice::forAdmin($admin)
            ->pending()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated paid invoices for admin
     */
    public function getPaginatedPaidForAdmin(User $admin, int $perPage = 15)
    {
        return SystemInvoice::forAdmin($admin)
            ->paid()
            ->with(['creator'])
            ->orderByDesc('paid_at')
            ->paginate($perPage);
    }

    /**
     * Get all pending invoices for admin
     */
    public function getPendingForAdmin(User $admin)
    {
        return SystemInvoice::forAdmin($admin)
            ->pending()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get paginated invoices created by super admin
     */
    public function getPaginatedCreatedByAdmin(User $createdBy, int $perPage = 15)
    {
        return SystemInvoice::createdBy($createdBy)
            ->with(['admin'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get system invoice by ID
     */
    public function getById(int $id)
    {
        return SystemInvoice::with(['admin', 'creator'])->findOrFail($id);
    }

    /**
     * Create new system invoice
     */
    public function create(array $data): SystemInvoice
    {
        return SystemInvoice::create($data);
    }

    /**
     * Update system invoice
     */
    public function update(SystemInvoice $invoice, array $data): bool
    {
        return $invoice->update($data);
    }

    /**
     * Delete system invoice
     */
    public function delete(SystemInvoice $invoice): bool
    {
        return $invoice->delete();
    }

    /**
     * Force delete system invoice
     */
    public function forceDelete(SystemInvoice $invoice): bool
    {
        return $invoice->forceDelete();
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats(User $user)
    {
        return [
            'pending_count' => SystemInvoice::forAdmin($user)->pending()->count(),
            'paid_count' => SystemInvoice::forAdmin($user)->paid()->count(),
            'pending_amount' => SystemInvoice::forAdmin($user)->pending()->sum('amount'),
            'paid_amount' => SystemInvoice::forAdmin($user)->paid()->sum('amount'),
        ];
    }
}
