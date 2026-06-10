<?php

namespace App\Exceptions\Redemption;

/** Gate 1: the member's account has not been verified yet. */
class NotVerifiedException extends RedemptionException
{
    protected $message = 'Your account is not verified yet, so you cannot redeem offers.';

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'not_verified';
    }
}
