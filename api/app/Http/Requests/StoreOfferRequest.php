<?php

namespace App\Http\Requests;

use App\Enums\Sector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'sector' => ['required', Rule::enum(Sector::class)],
            'discount_code' => ['required', 'string', 'max:255'],
            'discount_description' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
