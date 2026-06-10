<?php

namespace App\Services;

use App\Exceptions\Redemption\AlreadyRedeemedException;
use App\Exceptions\Redemption\CapacityReachedException;
use App\Exceptions\Redemption\NotVerifiedException;
use App\Exceptions\Redemption\OfferInactiveException;
use App\Exceptions\Redemption\OutsideWindowException;
use App\Exceptions\Redemption\SectorMismatchException;
use App\Models\Offer;
use App\Models\Redemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Decides whether a member may redeem an offer and, if so, issues the discount
 * code exactly once. This is the core of the app, so it's the file to read first.
 *
 * The six gates run in a deliberate order: cheapest and most fundamental checks
 * first (identity, then eligibility, then offer state and dates, then the
 * one-per-member and capacity rules that actually touch other rows). The member
 * gets back the single most relevant reason, and each failure throws a specific
 * RedemptionException carrying its own HTTP status and error code. Those are
 * mapped to JSON in one place (bootstrap/app.php), which keeps the controller a
 * thin pass-through.
 *
 * Keeping this in a service rather than the controller is the same split I rely
 * on in .NET: HTTP concerns in the controller, business rules in a plain,
 * unit-testable class with no request/response coupling.
 */
class RedemptionService
{
    /**
     * Redeem an offer for a member, or throw the reason it can't be redeemed.
     *
     * @throws NotVerifiedException|OfferInactiveException|SectorMismatchException
     * @throws OutsideWindowException|AlreadyRedeemedException|CapacityReachedException
     */
    public function redeem(User $user, Offer $offer): Redemption
    {
        $this->assertRedeemable($user, $offer);

        // The insert and the counter increment run together so they either both
        // land or neither does. The unique(user_id, offer_id) index is the real
        // safety net here: if two requests slip past assertRedeemable() at the
        // same moment, the second insert hits the index and this transaction
        // rolls back, leaving no duplicate code and no inflated count. The
        // in-code check just turns the common case into a clean 409.
        return DB::transaction(function () use ($user, $offer): Redemption {
            $redemption = $user->redemptions()->create([
                'offer_id' => $offer->id,
                'code_issued' => $offer->discount_code,
                'redeemed_at' => now(),
            ]);

            $offer->increment('redemption_count');

            return $redemption;
        });
    }

    /**
     * Runs the six gates in order and throws on the first one that fails.
     */
    private function assertRedeemable(User $user, Offer $offer): void
    {
        // 1. Only verified members can redeem at all.
        if (! $user->isVerified()) {
            throw new NotVerifiedException;
        }

        // 2. The offer has to be switched on.
        if (! $offer->is_active) {
            throw new OfferInactiveException;
        }

        // 3. The headline rule: the offer must target the member's verified
        //    sector. Both sides are Sector enums, so this is a strict compare.
        if ($offer->sector !== $user->sector) {
            throw new SectorMismatchException;
        }

        // 4. Within the offer's window. Tell the member which side they're on.
        $now = now();
        if ($now->lt($offer->starts_at)) {
            throw new OutsideWindowException('This offer has not started yet.');
        }
        if ($now->gt($offer->expires_at)) {
            throw new OutsideWindowException('This offer has expired.');
        }

        // 5. One redemption per member per offer (also enforced by the index).
        if ($user->redemptions()->where('offer_id', $offer->id)->exists()) {
            throw new AlreadyRedeemedException;
        }

        // 6. Respect the global cap when the offer sets one.
        if ($offer->max_redemptions !== null
            && $offer->redemption_count >= $offer->max_redemptions) {
            throw new CapacityReachedException;
        }
    }
}
