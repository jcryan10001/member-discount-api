<?php

namespace App\Exceptions\Redemption;

/** Gate 5: this member has already redeemed this offer (one per member). */
class AlreadyRedeemedException extends RedemptionException
{
    protected $message = 'You have already redeemed this offer.';

    public function status(): int
    {
        // 409 Conflict: the request conflicts with the current state (a
        // redemption for this member+offer already exists).
        return 409;
    }

    public function errorCode(): string
    {
        return 'already_redeemed';
    }
}
