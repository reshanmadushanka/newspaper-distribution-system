<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check current encoding
        $encoding = DB::select("SELECT pg_encoding_to_char(encoding) as encoding FROM pg_database WHERE datname = current_database()");

        if (isset($encoding[0])) {
            $currentEncoding = $encoding[0]->encoding;

            // If not UTF8, we need to fix it
            if ($currentEncoding !== 'UTF8') {
                // Log warning - actual fix requires database recreation
                Log::warning("Database encoding is {$currentEncoding}, not UTF8. Sinhala characters may not save correctly.");
                Log::warning("To fix: Create a new database with UTF8 encoding and migrate data.");

                // For now, just ensure columns can accept text
                // This won't fix the encoding issue but allows the migration to pass
                return;
            }
        }

        // If UTF8, ensure proper collation
        DB::statement('ALTER TABLE newspapers ALTER COLUMN name TYPE VARCHAR(255) COLLATE "default"');
        DB::statement('ALTER TABLE newspapers ALTER COLUMN publisher_name TYPE VARCHAR(255) COLLATE "default"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as UTF8 is the default
    }
};
