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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->string('preferred_invoice_delivery')->nullable(); // print/email/whatsapp
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->string('status')->default('active'); // active/inactive
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }

};
