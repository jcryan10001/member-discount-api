<?php

namespace App\Enums;

use App\Models\VerificationRequest;

/**
 * A member's verification state.
 *
 * Only {@see self::Verified} members can redeem offers. A member becomes
 * verified when an admin approves their {@see VerificationRequest}.
 */
enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
