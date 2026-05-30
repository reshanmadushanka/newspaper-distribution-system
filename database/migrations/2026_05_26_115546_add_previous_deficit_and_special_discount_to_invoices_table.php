<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('previous_deficit', 12, 2)->default(0)->after('notes');
            $table->decimal('special_discount', 12, 2)->default(0)->after('previous_deficit');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['previous_deficit', 'special_discount']);
        });
    }
};
