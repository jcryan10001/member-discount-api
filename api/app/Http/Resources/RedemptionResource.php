<?php

namespace App\Http\Resources;

use App\Models\Redemption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the JSON for a redemption. This is the only place the earned discount
 * code (`code_issued`) is returned.
 *
 * @mixin Redemption
 */
class RedemptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code_issued' => $this->code_issued,
            'redeemed_at' => $this->redeemed_at,
            'offer' => new OfferResource($this->whenLoaded('offer')),
        ];
    }
}
