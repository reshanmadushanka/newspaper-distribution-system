<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending/paid
            $table->text('bank_account_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // bank_transfer/check/cash
            $table->text('payment_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('admin_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_invoices');
    }
};
