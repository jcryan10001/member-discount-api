<?php

namespace App\Exceptions\Redemption;

/** Gate 6: the offer has hit its global redemption cap. */
class CapacityReachedException extends RedemptionException
{
    protected $message = 'This offer has been fully claimed.';

    public function status(): int
    {
        // 409 Conflict, like a sold-out resource: the offer's full state
        // conflicts with issuing another code. The error code distinguishes it
        // from the "already_redeemed" 409.
        return 409;
    }

    public function errorCode(): string
    {
        return 'capacity_reached';
    }
}
