<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePolicyTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('policy_types', 'name')->ignore($this->route('policyType')),
            ],
        ];
    }

    /**
     * This endpoint is only ever called via the settings page's JSON fetch
     * client, so always respond with JSON errors instead of the app's
     * default redirect (the global exception handler only renders JSON for
     * api/* routes).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422),
        );
    }
}
