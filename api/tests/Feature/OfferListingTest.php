<?php

namespace Tests\Feature;

use App\Enums\Sector;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_only_active_offers_in_their_sector(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();

        $visible = Offer::factory()->sector(Sector::Healthcare)
            ->create(['title' => 'Healthcare Active', 'discount_code' => 'SECRETCODE']);
        Offer::factory()->sector(Sector::Education)->create(['title' => 'Education Active']);
        Offer::factory()->sector(Sector::Healthcare)->inactive()->create(['title' => 'Healthcare Inactive']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);

        // The discount code is the reward, so it must never appear in the listing.
        $response->assertDontSee('SECRETCODE');
    }

    public function test_offers_are_sorted_newest_first(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $older = Offer::factory()->sector(Sector::Healthcare)->create(['created_at' => now()->subDay()]);
        $newer = Offer::factory()->sector(Sector::Healthcare)->create(['created_at' => now()]);

        Sanctum::actingAs($user);

        $this->getJson('/api/offers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_listing_requires_authentication(): void
    {
        $this->getJson('/api/offers')->assertUnauthorized();
    }
}
