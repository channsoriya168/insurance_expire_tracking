<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateInsuranceRequest extends StoreInsuranceRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'policy_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('insurances', 'policy_no')->ignore($this->route('insurance')),
            ],
        ];
    }
}
