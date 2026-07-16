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
     * Status and payment status are hidden on the create form and always
     * default to their initial state, so fill them in before validation
     * runs the `required` rule against them.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?: 'Pending',
            'payment_status' => $this->input('payment_status') ?: 'Unpaid',
        ]);
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
            'net_premium' => ['required', 'numeric', 'min:0'],
            'revised_sum_insured' => ['required', 'numeric', 'min:0'],
            'revised_premium' => ['required', 'numeric', 'min:0'],
            'revised_premium_rate' => ['required', 'numeric', 'min:0'],
            'confirmed_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(PolicyFieldSteps::statuses())],
            'payment_status' => ['required', Rule::in(PolicyFieldSteps::paymentStatuses())],
            'payment_date' => ['nullable', 'date'],
            'policy_received_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
