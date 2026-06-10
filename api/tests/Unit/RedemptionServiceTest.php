<?php

namespace Tests\Unit;

use App\Enums\Sector;
use App\Enums\VerificationStatus;
use App\Exceptions\Redemption\AlreadyRedeemedException;
use App\Exceptions\Redemption\CapacityReachedException;
use App\Exceptions\Redemption\NotVerifiedException;
use App\Exceptions\Redemption\OfferInactiveException;
use App\Exceptions\Redemption\OutsideWindowException;
use App\Exceptions\Redemption\SectorMismatchException;
use App\Models\Brand;
use App\Models\Offer;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises the redemption centerpiece directly (no HTTP): one test per gate,
 * plus the happy path, the counter increment, and the DB-level uniqueness
 * backstop. This is the highest-value suite in the project; it pins the
 * business rules in isolation from the framework's request/response layer.
 *
 * It touches the database (the "already redeemed" check and the write are real
 * Eloquent), so it extends the full-app TestCase with RefreshDatabase rather
 * than the bare PHPUnit TestCase.
 */
class RedemptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RedemptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RedemptionService;
    }

    public function test_verified_member_redeems_a_matching_offer(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer(['sector' => Sector::Healthcare, 'discount_code' => 'NHS20']);

        $redemption = $this->service->redeem($user, $offer);

        $this->assertSame('NHS20', $redemption->code_issued);
        $this->assertSame(1, $offer->fresh()->redemption_count);
        $this->assertDatabaseHas('redemptions', [
            'user_id' => $user->id,
            'offer_id' => $offer->id,
            'code_issued' => 'NHS20',
        ]);
    }

    public function test_unverified_member_cannot_redeem(): void
    {
        $user = $this->member(Sector::Healthcare); // status: pending
        $offer = $this->offer(['sector' => Sector::Healthcare]);

        $this->expectException(NotVerifiedException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_inactive_offer_cannot_be_redeemed(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer(['is_active' => false]);

        $this->expectException(OfferInactiveException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_member_cannot_redeem_a_different_sector_offer(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer(['sector' => Sector::Education]);

        $this->expectException(SectorMismatchException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_offer_not_yet_started_cannot_be_redeemed(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer([
            'starts_at' => now()->addDay(),
            'expires_at' => now()->addDays(10),
        ]);

        $this->expectException(OutsideWindowException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_expired_offer_cannot_be_redeemed(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer([
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $this->expectException(OutsideWindowException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_member_cannot_redeem_the_same_offer_twice(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer();

        $this->service->redeem($user, $offer);

        $this->expectException(AlreadyRedeemedException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_fully_claimed_offer_cannot_be_redeemed(): void
    {
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer(['max_redemptions' => 1, 'redemption_count' => 1]);

        $this->expectException(CapacityReachedException::class);
        $this->service->redeem($user, $offer);
    }

    public function test_unique_index_blocks_duplicate_at_db_level(): void
    {
        // Prove the safety net independently of the in-code check: inserting two
        // redemptions for the same member+offer must fail at the database.
        $user = $this->verifiedMember(Sector::Healthcare);
        $offer = $this->offer();

        $user->redemptions()->create(['offer_id' => $offer->id, 'code_issued' => 'X', 'redeemed_at' => now()]);

        $this->expectException(QueryException::class);
        $user->redemptions()->create(['offer_id' => $offer->id, 'code_issued' => 'X', 'redeemed_at' => now()]);
    }

    private function member(Sector $sector): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@demo.test',
            'password' => 'password',
            'sector' => $sector,
        ]);
    }

    private function verifiedMember(Sector $sector): User
    {
        $user = $this->member($sector);
        $user->verification_status = VerificationStatus::Verified;
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function offer(array $overrides = []): Offer
    {
        $brand = Brand::create(['name' => 'Brand', 'description' => 'A demo brand']);

        return Offer::create(array_merge([
            'brand_id' => $brand->id,
            'title' => 'Demo Offer',
            'description' => 'A demo offer',
            'sector' => Sector::Healthcare,
            'discount_code' => 'SAVE20',
            'discount_description' => '20% off',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ], $overrides));
    }
}
