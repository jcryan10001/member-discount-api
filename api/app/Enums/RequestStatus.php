<?php

namespace App\Enums;

use App\Models\VerificationRequest;

/**
 * The state of a {@see VerificationRequest} as an admin reviews it.
 *
 * Deliberately distinct from {@see VerificationStatus}: a *request* is
 * "approved" (a decision about the submitted proof), which in turn flips the
 * *member's* status to "verified". Keeping the two vocabularies separate makes
 * the review workflow read honestly.
 */
enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
