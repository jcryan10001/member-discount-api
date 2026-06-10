<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
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
            // Simulated proof of eligibility, e.g. "NHS staff ID: ABC-12345".
            // A production system would accept a file upload instead (README).
            'proof_reference' => ['required', 'string', 'max:1000'],
        ];
    }
}
