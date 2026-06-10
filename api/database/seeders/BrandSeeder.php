<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Real partner brands, for domain credibility in the demo. OfferSeeder looks
 * these up by name.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Apple', 'description' => 'Consumer electronics: iPhone, Mac, iPad and accessories.', 'website' => 'https://www.apple.com'],
            ['name' => 'Nike', 'description' => 'Sportswear, footwear and equipment.', 'website' => 'https://www.nike.com'],
            ['name' => 'Pandora', 'description' => 'Jewellery, charms and bracelets.', 'website' => 'https://www.pandora.net'],
            ['name' => 'New Look', 'description' => 'High-street fashion and accessories.', 'website' => 'https://www.newlook.com'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
