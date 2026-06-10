<?php

namespace Tests\Feature;

use App\Enums\Sector;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Drives every RedemptionService gate through the real HTTP endpoint, asserting
 * the status code AND the machine-readable error code the frontend relies on.
 * These are the failure responses worth showing off in the README.
 */
class RedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_member_redeems_a_matching_offer(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->create(['discount_code' => 'NHS20']);

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertCreated()
            ->assertJsonPath('data.code_issued', 'NHS20')
            ->assertJsonPath('data.offer.id', $offer->id);

        $this->assertSame(1, $offer->fresh()->redemption_count);
        $this->assertDatabaseHas('redemptions', ['user_id' => $user->id, 'offer_id' => $offer->id]);
    }

    public function test_unverified_member_cannot_redeem(): void
    {
        $user = User::factory()->sector(Sector::Healthcare)->create(); // pending
        $offer = Offer::factory()->sector(Sector::Healthcare)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(403)
            ->assertJsonPath('error', 'not_verified');
    }

    public function test_member_cannot_redeem_an_offer_for_a_different_sector(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Education)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(403)
            ->assertJsonPath('error', 'sector_mismatch');
    }

    public function test_member_cannot_redeem_an_inactive_offer(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->inactive()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(422)
            ->assertJsonPath('error', 'offer_inactive');
    }

    public function test_member_cannot_redeem_an_expired_offer(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->expired()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(422)
            ->assertJsonPath('error', 'outside_window');
    }

    public function test_member_cannot_redeem_the_same_offer_twice(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")->assertCreated();
        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(409)
            ->assertJsonPath('error', 'already_redeemed');
    }

    public function test_member_cannot_redeem_a_fully_claimed_offer(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->atCapacity()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/offers/{$offer->id}/redeem")
            ->assertStatus(409)
            ->assertJsonPath('error', 'capacity_reached');
    }

    public function test_redemption_history_lists_the_members_redemptions(): void
    {
        $user = User::factory()->verified()->sector(Sector::Healthcare)->create();
        $offer = Offer::factory()->sector(Sector::Healthcare)->create(['discount_code' => 'HIST10']);

        Sanctum::actingAs($user);
        $this->postJson("/api/offers/{$offer->id}/redeem")->assertCreated();

        $this->getJson('/api/redemptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code_issued', 'HIST10');
    }

    public function test_redeeming_requires_authentication(): void
    {
        $offer = Offer::factory()->create();

        $this->postJson("/api/offers/{$offer->id}/redeem")->assertUnauthorized();
    }
}
