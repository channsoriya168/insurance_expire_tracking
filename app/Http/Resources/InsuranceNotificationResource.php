<?php

namespace App\Http\Resources;

use App\Models\InsuranceNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InsuranceNotification */
class InsuranceNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->insurance_id,
            'policy_no' => $this->insurance->policy_no,
            'insured_name' => $this->insurance->insured_name,
            'insurance_company' => $this->insurance->insurance_company,
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'created_at' => $this->created_at->format('Y-m-d'),
            'read' => $this->read_at !== null,
            'bucket' => $this->bucket,
        ];
    }
}
