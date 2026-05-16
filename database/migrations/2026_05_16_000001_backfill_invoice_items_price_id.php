<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update price_id only where there's an exact match between newspaper_prices and invoice_items
        DB::statement('
            UPDATE invoice_items
            SET price_id = newspaper_prices.id
            FROM newspaper_prices
            WHERE invoice_items.newspaper_id = newspaper_prices.newspaper_id
            AND invoice_items.unit_price = newspaper_prices.price
            AND invoice_items.price_id IS NULL
        ');

        // Log how many records were updated
        $updatedCount = DB::table('invoice_items')
            ->whereNotNull('price_id')
            ->count();

        $totalCount = DB::table('invoice_items')->count();

        echo "Updated price_id for {$updatedCount} out of {$totalCount} invoice items\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all price_id values back to NULL
        DB::table('invoice_items')
            ->update(['price_id' => null]);
    }
};
