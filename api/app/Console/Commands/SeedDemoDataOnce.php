<?php

namespace App\Console\Commands;

use App\Models\ProductionLine;
use Illuminate\Console\Command;

class SeedDemoDataOnce extends Command
{
    protected $signature = 'demo:seed-once';

    protected $description = 'Seed the demo board, but only if the database is empty — safe to run on every boot';

    /**
     * Render's free tier has no Shell access, so a one-time manual `db:seed` (the pattern used
     * on GreenStock, which assumed shell access) isn't an option here. Running the raw seeder
     * unconditionally on every boot would append duplicate production lines and work orders on
     * every free-tier wake-from-sleep restart, so this guards it: seed once, then no-op forever.
     */
    public function handle(): int
    {
        if (ProductionLine::query()->exists()) {
            $this->info('Demo data already present — skipping.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--force' => true]);

        $this->info('Demo data seeded.');

        return self::SUCCESS;
    }
}
