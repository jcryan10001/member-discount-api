<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the JSON for an offer.
 *
 * Deliberately omits `discount_code`: the code is the reward, issued only on a
 * successful redemption (see RedemptionResource). Browsing offers shows the
 * human-readable `discount_description` ("20% off") but never the code itself.
 * Otherwise the redemption gate would mean nothing.
 *
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'sector' => $this->sector?->value,
            'discount_description' => $this->discount_description,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'max_redemptions' => $this->max_redemptions,
            'redemption_count' => $this->redemption_count,
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
