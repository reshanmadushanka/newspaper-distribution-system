<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['price_id']);
            $table->dropColumn('price_id');
        });

        Schema::dropIfExists('newspaper_prices');
    }

    public function down(): void
    {
        Schema::create('newspaper_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newspaper_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('price_id')->after('newspaper_id')->constrained('newspaper_prices')->cascadeOnDelete();
        });
    }
};
