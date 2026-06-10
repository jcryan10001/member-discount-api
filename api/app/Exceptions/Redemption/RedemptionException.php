<?php

namespace App\Exceptions\Redemption;

use Exception;

/**
 * Base class for every reason a redemption can be refused.
 *
 * Each concrete subclass carries its own HTTP status and a short, machine
 * readable error code, so the global handler in bootstrap/app.php can turn any
 * failure into one consistent JSON shape ({"error", "message"}) without a pile
 * of conditionals in the controller.
 *
 * Typed subclasses, rather than one exception plus a reason string, give a
 * single mapping point, keep the failure modes easy to grep for, and let a
 * specific failure carry extra data later without touching any caller. Same idea
 * as typed domain exceptions in .NET mapped centrally in middleware.
 */
abstract class RedemptionException extends Exception
{
    /** The HTTP status this failure maps to. */
    abstract public function status(): int;

    /** A stable, machine-readable code the client can branch on. */
    abstract public function errorCode(): string;
}
