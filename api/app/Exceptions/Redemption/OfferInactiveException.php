<?php

namespace App\Exceptions\Redemption;

/** Gate 2: the offer has been switched off. */
class OfferInactiveException extends RedemptionException
{
    protected $message = 'This offer is no longer available.';

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'offer_inactive';
    }
}
