<?php

namespace Tests\Feature;

use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductionLineApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_production_lines(): void
    {
        $this->getJson('/api/production-lines')->assertUnauthorized();
    }

    public function test_it_lists_production_lines(): void
    {
        ProductionLine::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/production-lines')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_creates_a_production_line(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/production-lines', ['name' => 'Assembly A', 'code' => 'L01']);

        $response->assertCreated()->assertJsonPath('data.code', 'L01');
        $this->assertDatabaseHas('production_lines', ['code' => 'L01']);
    }

    public function test_code_must_be_unique(): void
    {
        ProductionLine::factory()->create(['code' => 'L01']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/production-lines', ['name' => 'Assembly B', 'code' => 'L01']);

        $response->assertStatus(422)->assertJsonValidationErrors(['code']);
    }
}
