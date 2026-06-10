<?php

namespace Database\Factories;

use App\Enums\Sector;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Default: an unverified member in a random sector. Factories run inside
     * Model::unguarded(), so they may set the guarded verification_status /
     * is_admin columns that request input cannot.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'sector' => fake()->randomElement(Sector::cases()),
            'verification_status' => VerificationStatus::Pending,
            'is_admin' => false,
        ];
    }

    /** A member who has passed verification (can redeem). */
    public function verified(): static
    {
        return $this->state(['verification_status' => VerificationStatus::Verified]);
    }

    /** Pin the member to a specific sector. */
    public function sector(Sector $sector): static
    {
        return $this->state(['sector' => $sector]);
    }

    /** An admin user. */
    public function admin(): static
    {
        return $this->state(['is_admin' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
