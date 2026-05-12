<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences';
    protected $description = 'Reset all PostgreSQL sequences to match current max IDs';

    public function handle(): int
    {
        $tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");

        foreach ($tables as $table) {
            $name = $table->tablename;
            $seq = "{$name}_id_seq";

            $exists = DB::select("SELECT 1 FROM pg_class WHERE relname = ?", [$seq]);
            if (empty($exists)) {
                continue;
            }

            DB::statement("SELECT setval('{$seq}', COALESCE((SELECT MAX(id) FROM {$name}), 1))");
            $this->info("{$seq} fixed");
        }

        $this->info('All sequences reset.');

        return self::SUCCESS;
    }
}
