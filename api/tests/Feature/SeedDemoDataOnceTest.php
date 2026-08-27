<?php

namespace Tests\Feature;

use App\Models\ProductionLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDemoDataOnceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_board_when_the_database_is_empty(): void
    {
        $this->assertDatabaseCount('production_lines', 0);

        $this->artisan('demo:seed-once')->assertSuccessful();

        $this->assertDatabaseCount('production_lines', 4);
        $this->assertDatabaseCount('work_orders', 11);
    }

    public function test_it_skips_seeding_when_data_already_exists(): void
    {
        ProductionLine::factory()->create();

        $this->artisan('demo:seed-once')->assertSuccessful();

        // Only the one factory-created line — the seeder never ran.
        $this->assertDatabaseCount('production_lines', 1);
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_running_it_twice_does_not_duplicate_data(): void
    {
        $this->artisan('demo:seed-once')->assertSuccessful();
        $this->artisan('demo:seed-once')->assertSuccessful();

        $this->assertDatabaseCount('production_lines', 4);
    }
}
