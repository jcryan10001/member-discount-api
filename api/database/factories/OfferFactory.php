<?php

namespace Database\Factories;

use App\Enums\Sector;
use App\Models\Brand;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 *
 * States map one-to-one onto the RedemptionService gates, so a test can set up
 * exactly the failure it wants: ->expired(), ->inactive(), ->atCapacity().
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'sector' => Sector::Healthcare,
            'discount_code' => strtoupper(fake()->bothify('SAVE-####')),
            'discount_description' => fake()->randomElement(['10% off', '20% off', 'Free delivery']),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'max_redemptions' => null,
            'redemption_count' => 0,
        ];
    }

    public function sector(Sector $sector): static
    {
        return $this->state(['sector' => $sector]);
    }

    /** Active, but its window is in the past. */
    public function expired(): static
    {
        return $this->state([
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** redemption_count has hit max_redemptions. */
    public function atCapacity(): static
    {
        return $this->state(['max_redemptions' => 1, 'redemption_count' => 1]);
    }
}
