<?php

namespace App\Telegram\Conversations;

use App\Enums\ContactMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;

/**
 * Declarative catalogue of the 20 Insurance fields, shared by the form
 * controller and form request so field order and contact methods aren't
 * duplicated.
 */
final class PolicyFieldSteps
{
    /**
     * @var list<string>
     */
    public const array ORDER = [
        'insurance_company',
        'policy_no',
        'contact_method',
        'contact_value',
        'contact_person',
        'insured_name',
        'expiry_date',
        'policy_type',
        'sum_insured',
        'premium',
        'net_premium',
        'revised_sum_insured',
        'revised_premium',
        'revised_premium_rate',
        'confirmed_date',
        'status',
        'payment_status',
        'payment_date',
        'policy_received_date',
        'remarks',
    ];

    /**
     * @return list<string>
     */
    public static function contactMethods(): array
    {
        return ContactMethod::values();
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return PolicyStatus::values();
    }

    /**
     * @return list<string>
     */
    public static function paymentStatuses(): array
    {
        return PaymentStatus::values();
    }
}
