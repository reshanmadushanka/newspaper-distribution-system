<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('invoice_date');
            $table->index('status');
            $table->index('deleted_at');
            $table->index(['invoice_date', 'deleted_at']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['invoice_date', 'deleted_at']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });
    }
};
