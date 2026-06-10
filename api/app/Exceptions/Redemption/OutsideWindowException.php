<?php

namespace App\Exceptions\Redemption;

/**
 * Gate 4: the current time is outside the offer's [starts_at, expires_at]
 * window. The service passes a specific message ("not started" vs "expired")
 * so the member sees exactly why.
 */
class OutsideWindowException extends RedemptionException
{
    protected $message = 'This offer is not currently available.';

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'outside_window';
    }
}
