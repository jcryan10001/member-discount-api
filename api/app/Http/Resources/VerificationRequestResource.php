<?php

namespace App\Http\Resources;

use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VerificationRequest
 */
class VerificationRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proof_reference' => $this->proof_reference,
            'status' => $this->status?->value,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            // The submitting member, loaded for the admin review queue.
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
