<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Sector;
use App\Enums\VerificationStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A platform member (admins are just users with is_admin = true).
 *
 * Intentionally NOT mass-assignable: `is_admin` and `verification_status`.
 * Both grant privileges, so they're set explicitly (seeders, the admin approval
 * flow) rather than from request input. That closes the classic mass-assignment
 * privilege-escalation hole. Registration may only set name/email/password/sector.
 */
#[Fillable(['name', 'email', 'password', 'sector'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Model-level defaults so a freshly-created member is consistent in memory
     * (not just once reloaded from the DB), e.g. the register response reports
     * verification_status = "pending" without a round-trip.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => VerificationStatus::Pending->value,
        'is_admin' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sector' => Sector::class,
            'verification_status' => VerificationStatus::class,
            'is_admin' => 'boolean',
        ];
    }

    /** Gate #1 in RedemptionService: only verified members may redeem. */
    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    /** @return HasMany<VerificationRequest, $this> */
    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    /** @return HasMany<Redemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }
}
