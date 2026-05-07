<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('restrict');
            $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
            $table->date('invoice_date');
            $table->date('dispatch_date');
            $table->string('status')->default('draft');
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->decimal('return_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->decimal('balance_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'invoice_date']);
            $table->index(['dispatch_date']);
            $table->index(['status']);
            $table->unique(['invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
