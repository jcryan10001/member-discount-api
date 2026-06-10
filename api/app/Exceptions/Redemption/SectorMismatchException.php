<?php

namespace App\Exceptions\Redemption;

/**
 * Gate 3: the offer targets a different sector than the member is verified for.
 * This is the headline business rule: a verified healthcare worker cannot
 * redeem a teacher-only offer.
 */
class SectorMismatchException extends RedemptionException
{
    protected $message = 'This offer is not available for your verified sector.';

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'sector_mismatch';
    }
}
