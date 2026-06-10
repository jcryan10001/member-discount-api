<?php

namespace App\Http\Requests;

use App\Enums\Sector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Partial update, typically toggling is_active or tweaking copy.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'sector' => ['sometimes', Rule::enum(Sector::class)],
            'discount_code' => ['sometimes', 'required', 'string', 'max:255'],
            'discount_description' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'expires_at' => ['sometimes', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
