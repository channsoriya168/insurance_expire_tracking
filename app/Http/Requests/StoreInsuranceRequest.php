<?php

namespace App\Http\Requests;

use App\Telegram\Conversations\PolicyFieldSteps;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceRequest extends FormRequest
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
            'insurance_company' => ['required', 'string', 'max:255'],
            'policy_no' => ['required', 'string', 'max:255', Rule::unique('insurances', 'policy_no')],
            'contact_method' => ['required', Rule::in(PolicyFieldSteps::contactMethods())],
            'contact_value' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'insured_name' => ['required', 'string', 'max:255'],
            'expiry_date' => ['required', 'date'],
            'policy_type' => ['required', 'string', 'max:255'],
            'sum_insured' => ['required', 'numeric', 'min:0'],
            'premium' => ['required', 'numeric', 'min:0'],
            'revised_sum_insured' => ['nullable', 'numeric', 'min:0'],
            'revised_premium' => ['nullable', 'numeric', 'min:0'],
            'revised_premium_rate' => ['nullable', 'numeric', 'min:0'],
            'confirmed_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
            'request_policy_date' => ['nullable', 'date'],
            'policy_received_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
