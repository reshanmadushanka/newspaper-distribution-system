<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_forecasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('newspaper_id')->constrained('newspapers')->onDelete('cascade');
            $table->date('forecast_date');
            $table->integer('suggested_quantity');
            $table->integer('final_quantity')->nullable();
            $table->string('method');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->jsonb('source_data');
            $table->timestamps();
            $table->index(['shop_id', 'forecast_date']);
            $table->index(['newspaper_id', 'forecast_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_forecasts');
    }
};
