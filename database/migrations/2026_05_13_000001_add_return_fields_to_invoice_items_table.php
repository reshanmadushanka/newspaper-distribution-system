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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('return_quantity')->default(0)->after('quantity');
            $table->decimal('return_total_price', 12, 2)->default(0)->after('total_price');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('return_total_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('total_net_amount', 12, 2)->default(0)->after('return_total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['return_quantity', 'return_total_price']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['return_total_amount', 'total_net_amount']);
        });
    }
};
