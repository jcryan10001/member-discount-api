<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the JSON for a user. API Resources are the response-side counterpart to
 * Form Requests: a single, explicit place that decides what leaves the system,
 * so internal columns (password hash, remember_token) never leak by accident.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'sector' => $this->sector?->value,
            'verification_status' => $this->verification_status?->value,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at,
        ];
    }
}
