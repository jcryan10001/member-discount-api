<?php

namespace App\Enums;

use App\Services\RedemptionService;

/**
 * The audience segments the platform serves.
 *
 * A member is verified for exactly one sector and may only redeem offers
 * targeted at that same sector. That is the headline eligibility rule, enforced in
 * {@see RedemptionService}.
 *
 * Backed by strings so the value stores readably in SQLite (which has no native
 * ENUM type) and serialises cleanly to JSON, while staying type-safe in PHP.
 */
enum Sector: string
{
    case Healthcare = 'healthcare';
    case Education = 'education';
    case Charity = 'charity';
    case Carer = 'carer';
}
