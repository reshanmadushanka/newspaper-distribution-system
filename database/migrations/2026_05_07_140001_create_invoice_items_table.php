<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('newspaper_id')->constrained('newspapers')->onDelete('restrict');
            $table->string('newspaper_code');
            $table->string('newspaper_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 12, 2);
            $table->integer('forecast_quantity')->nullable();
            $table->text('manual_adjustment_reason')->nullable();
            $table->timestamps();
            $table->index(['invoice_id']);
            $table->index(['newspaper_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
