<?php

use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            INSERT INTO newspaper_prices (newspaper_id, label, price, cost_price, created_at, updated_at)
            SELECT id, NULL, price, cost_price, NOW(), NOW()
            FROM newspapers
            WHERE price IS NOT NULL
        ');
    }

    public function down(): void
    {
        Newspaper::query()->whereNull('deleted_at')->each(function (Newspaper $newspaper) {
            $newspaper->prices()->delete();
        });
    }
};
