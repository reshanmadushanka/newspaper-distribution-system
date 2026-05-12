<?php

namespace App\Domain\Admin\Models;

use App\Domain\Admin\Enums\SystemInvoiceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'admin_id',
    'created_by',
    'amount',
    'reason',
    'description',
    'status',
    'bank_account_details',
    'paid_at',
    'payment_method',
    'payment_notes',
])]
class SystemInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'system_invoices';

    protected $casts = [
        'status' => SystemInvoiceStatus::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who the invoice is for
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the user who created the invoice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(?string $paymentMethod = null, ?string $paymentNotes = null): void
    {
        $this->update([
            'status' => SystemInvoiceStatus::PAID,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_notes' => $paymentNotes,
        ]);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === SystemInvoiceStatus::PAID;
    }

    /**
     * Check if invoice is pending
     */
    public function isPending(): bool
    {
        return $this->status === SystemInvoiceStatus::PENDING;
    }

    /**
     * Scope for pending invoices
     */
    public function scopePending($query)
    {
        return $query->where('status', SystemInvoiceStatus::PENDING);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', SystemInvoiceStatus::PAID);
    }

    /**
     * Scope for invoices for a specific admin
     */
    public function scopeForAdmin($query, User $admin)
    {
        return $query->where('admin_id', $admin->id);
    }

    /**
     * Scope for invoices created by a specific user
     */
    public function scopeCreatedBy($query, User $user)
    {
        return $query->where('created_by', $user->id);
    }
}
