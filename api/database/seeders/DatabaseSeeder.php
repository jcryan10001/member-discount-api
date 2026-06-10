<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BrandSeeder::class,
            OfferSeeder::class,
        ]);

        // Pre-seed one redemption so the nurse's history isn't empty on first
        // login, and so re-redeeming this offer demonstrates the
        // "already redeemed" (409) gate.
        $nurse = User::where('email', 'nurse@demo.test')->first();
        $offer = Offer::where('discount_code', 'NIKE-HEALTH-FD')->first();

        $nurse->redemptions()->create([
            'offer_id' => $offer->id,
            'code_issued' => $offer->discount_code,
            'redeemed_at' => now()->subDays(2),
        ]);
        $offer->increment('redemption_count');
    }
}
