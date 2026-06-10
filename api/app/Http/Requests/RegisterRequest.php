<?php

namespace App\Http\Requests;

use App\Enums\Sector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validates member registration input.
 *
 * Form Requests are Laravel's idiomatic place for input validation, the .NET
 * equivalent of model validation / a FluentValidation validator. Keeping rules
 * here means the controller only ever sees already-valid data. Authorization is
 * handled separately at the route layer (auth:sanctum / can:admin), so
 * authorize() just permits the request to proceed to validation.
 */
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            // Validated against the Sector enum, so only the four real sectors pass.
            'sector' => ['required', Rule::enum(Sector::class)],
        ];
    }
}
