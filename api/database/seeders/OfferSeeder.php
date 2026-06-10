<?php

namespace Database\Seeders;

use App\Enums\Sector;
use App\Models\Brand;
use App\Models\Offer;
use Illuminate\Database\Seeder;

/**
 * Offers chosen so the seeded nurse (verified, healthcare) can exercise every
 * RedemptionService gate. By discount code:
 *
 *   APPLE-HEALTH-20    clean redeem (success)
 *   PANDORA-HEALTH-15  expired window     (outside_window)
 *   NEWLOOK-HEALTH-25  fully claimed      (capacity_reached)
 *   NIKE-HEALTH-FD     already redeemed   (pre-seeded in DatabaseSeeder)
 *   PANDORA-HEALTH-10  inactive offer     (offer_inactive, hit via the API)
 *   APPLE-EDU-10       wrong sector       (sector_mismatch, hit via the API)
 *
 * A charity and a carer offer round it out so those sectors aren't empty.
 */
class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::all()->keyBy('name');

        $offers = [
            // Healthcare. The nurse sees these four active ones in her list.
            [
                'brand' => 'Apple', 'sector' => Sector::Healthcare,
                'title' => '20% off Mac & iPad', 'description' => 'Save 20% on any Mac or iPad for NHS staff.',
                'discount_code' => 'APPLE-HEALTH-20', 'discount_description' => '20% off',
                'starts_at' => now()->subDays(7), 'expires_at' => now()->addMonth(),
            ],
            [
                'brand' => 'Pandora', 'sector' => Sector::Healthcare,
                'title' => '15% off all charms', 'description' => 'A treat for healthcare workers, now ended.',
                'discount_code' => 'PANDORA-HEALTH-15', 'discount_description' => '15% off',
                'starts_at' => now()->subMonths(2), 'expires_at' => now()->subDays(2), // expired window
            ],
            [
                'brand' => 'New Look', 'sector' => Sector::Healthcare,
                'title' => '25% off everything', 'description' => 'Hugely popular, now fully claimed.',
                'discount_code' => 'NEWLOOK-HEALTH-25', 'discount_description' => '25% off',
                'starts_at' => now()->subDays(10), 'expires_at' => now()->addMonth(),
                'max_redemptions' => 25, 'redemption_count' => 25, // at capacity
            ],
            [
                'brand' => 'Nike', 'sector' => Sector::Healthcare,
                'title' => 'Free delivery', 'description' => 'Free delivery on all orders for healthcare staff.',
                'discount_code' => 'NIKE-HEALTH-FD', 'discount_description' => 'Free delivery',
                'starts_at' => now()->subDays(10), 'expires_at' => now()->addMonth(),
            ],
            [
                'brand' => 'Pandora', 'sector' => Sector::Healthcare,
                'title' => '10% off (promo ended)', 'description' => 'An old promotion that has been switched off.',
                'discount_code' => 'PANDORA-HEALTH-10', 'discount_description' => '10% off',
                'starts_at' => now()->subDays(10), 'expires_at' => now()->addMonth(),
                'is_active' => false, // switched off
            ],

            // Education, teacher-only. Blocks the healthcare nurse on sector.
            [
                'brand' => 'Apple', 'sector' => Sector::Education,
                'title' => '10% off for teachers', 'description' => 'Education discount on Apple products.',
                'discount_code' => 'APPLE-EDU-10', 'discount_description' => '10% off',
                'starts_at' => now()->subDays(7), 'expires_at' => now()->addMonth(),
            ],

            // Charity and carer, so every sector has something.
            [
                'brand' => 'New Look', 'sector' => Sector::Charity,
                'title' => '20% off for charity workers', 'description' => 'Thank you to the charity sector.',
                'discount_code' => 'NEWLOOK-CHARITY-20', 'discount_description' => '20% off',
                'starts_at' => now()->subDays(7), 'expires_at' => now()->addMonth(),
            ],
            [
                'brand' => 'Nike', 'sector' => Sector::Carer,
                'title' => '15% off for carers', 'description' => 'A discount for unpaid and professional carers.',
                'discount_code' => 'NIKE-CARER-15', 'discount_description' => '15% off',
                'starts_at' => now()->subDays(7), 'expires_at' => now()->addMonth(),
            ],
        ];

        foreach ($offers as $offer) {
            $brand = $brands[$offer['brand']];
            unset($offer['brand']);

            $brand->offers()->create($offer);
        }
    }
}
