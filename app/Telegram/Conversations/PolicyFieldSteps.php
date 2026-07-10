<?php

namespace App\Telegram\Conversations;

use App\Models\Insurance;
use Carbon\Carbon;
use Throwable;

/**
 * Declarative catalogue of the 18 Insurance fields, shared by the /add and
 * /edit conversation flows so prompt text and validation aren't duplicated.
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
        'revised_sum_insured',
        'revised_premium',
        'revised_premium_rate',
        'confirmed_date',
        'status',
        'request_policy_date',
        'policy_received_date',
        'remarks',
    ];

    /**
     * @var list<string>
     */
    private const array CONTACT_METHODS = ['Email', 'WhatsApp', 'WeChat', 'Telegram'];

    /**
     * @var list<string>
     */
    private const array SKIP_TOKENS = ['-', 'skip'];

    /**
     * @return array<string, FieldStep>
     */
    public static function all(): array
    {
        return [
            'insurance_company' => new FieldStep('insurance_company', 'What is the insurance company? (e.g. Lonpac, Infinity)', false, self::text(...)),
            'policy_no' => new FieldStep('policy_no', 'What is the policy number?', false, self::policyNo(...)),
            'contact_method' => new FieldStep('contact_method', 'Contact method? (Email, WhatsApp, WeChat, or Telegram)', false, self::contactMethod(...)),
            'contact_value' => new FieldStep('contact_value', 'What is the contact email/phone/handle?', false, self::text(...)),
            'contact_person' => new FieldStep('contact_person', 'Who is the contact person?', false, self::text(...)),
            'insured_name' => new FieldStep('insured_name', "What is the insured/client's name?", false, self::text(...)),
            'expiry_date' => new FieldStep('expiry_date', 'What is the expiry date? (YYYY-MM-DD)', false, self::date(...)),
            'policy_type' => new FieldStep('policy_type', 'What is the policy type? (e.g. Fire, PAR, GPA, Motor Ins, CAR)', false, self::text(...)),
            'sum_insured' => new FieldStep('sum_insured', 'What is the sum insured?', false, self::decimal(...)),
            'premium' => new FieldStep('premium', 'What is the premium?', false, self::decimal(...)),
            'revised_sum_insured' => new FieldStep('revised_sum_insured', 'Revised sum insured? (send "-" to skip)', true, self::decimal(...)),
            'revised_premium' => new FieldStep('revised_premium', 'Revised premium? (send "-" to skip)', true, self::decimal(...)),
            'revised_premium_rate' => new FieldStep('revised_premium_rate', 'Revised premium rate? (send "-" to skip)', true, self::decimal(...)),
            'confirmed_date' => new FieldStep('confirmed_date', 'Confirmed date? (YYYY-MM-DD, send "-" to skip)', true, self::date(...)),
            'status' => new FieldStep('status', 'Status? (send "-" to skip, defaults to "Pending")', true, self::text(...)),
            'request_policy_date' => new FieldStep('request_policy_date', 'Request policy date? (YYYY-MM-DD, send "-" to skip)', true, self::date(...)),
            'policy_received_date' => new FieldStep('policy_received_date', 'Policy received date? (YYYY-MM-DD, send "-" to skip)', true, self::date(...)),
            'remarks' => new FieldStep('remarks', 'Any remarks? (send "-" to skip)', true, self::text(...)),
        ];
    }

    public static function get(string $key): ?FieldStep
    {
        return self::all()[$key] ?? null;
    }

    public static function next(string $key): ?string
    {
        $index = array_search($key, self::ORDER, true);

        return $index === false ? null : (self::ORDER[$index + 1] ?? null);
    }

    public static function isSkip(string $raw): bool
    {
        return in_array(strtolower(trim($raw)), self::SKIP_TOKENS, true);
    }

    public static function label(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @return list<string>
     */
    public static function contactMethods(): array
    {
        return self::CONTACT_METHODS;
    }

    public static function isDateField(string $key): bool
    {
        return str_ends_with($key, '_date');
    }

    public static function isNumericField(string $key): bool
    {
        return in_array($key, ['sum_insured', 'premium', 'revised_sum_insured', 'revised_premium', 'revised_premium_rate'], true);
    }

    public static function parse(string $key, string $raw, ?int $excludeInsuranceId = null): FieldParseResult
    {
        $step = self::get($key);

        if ($step === null) {
            return FieldParseResult::fail('Unknown field.');
        }

        if (self::isSkip($raw)) {
            if ($key === 'status') {
                return FieldParseResult::ok('Pending');
            }

            if (! $step->skippable) {
                return FieldParseResult::fail('This field is required and cannot be skipped.');
            }

            return FieldParseResult::ok(null);
        }

        return ($step->parse)($raw, $excludeInsuranceId);
    }

    private static function text(string $raw): FieldParseResult
    {
        $value = trim($raw);

        return $value === ''
            ? FieldParseResult::fail('This field cannot be empty.')
            : FieldParseResult::ok($value);
    }

    private static function policyNo(string $raw, ?int $excludeInsuranceId = null): FieldParseResult
    {
        $value = trim($raw);

        if ($value === '') {
            return FieldParseResult::fail('Policy number cannot be empty.');
        }

        $exists = Insurance::query()
            ->where('policy_no', $value)
            ->when($excludeInsuranceId !== null, fn ($query) => $query->where('id', '!=', $excludeInsuranceId))
            ->exists();

        return $exists
            ? FieldParseResult::fail("Policy number \"{$value}\" already exists. Please enter a different one.")
            : FieldParseResult::ok($value);
    }

    private static function contactMethod(string $raw): FieldParseResult
    {
        $value = trim($raw);

        foreach (self::CONTACT_METHODS as $method) {
            if (strcasecmp($method, $value) === 0) {
                return FieldParseResult::ok($method);
            }
        }

        return FieldParseResult::fail('Please choose one of: '.implode(', ', self::CONTACT_METHODS));
    }

    private static function date(string $raw): FieldParseResult
    {
        $raw = trim($raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
            return FieldParseResult::fail('Please send a valid date in YYYY-MM-DD format.');
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
        } catch (Throwable) {
            return FieldParseResult::fail('Please send a valid date in YYYY-MM-DD format.');
        }

        if ($date->format('Y-m-d') !== $raw) {
            return FieldParseResult::fail('Please send a valid date in YYYY-MM-DD format.');
        }

        return FieldParseResult::ok($raw);
    }

    private static function decimal(string $raw): FieldParseResult
    {
        $value = str_replace([',', '%', ' '], '', trim($raw));

        if (! is_numeric($value) || (float) $value < 0) {
            return FieldParseResult::fail('Please send a non-negative number.');
        }

        return FieldParseResult::ok((float) $value);
    }
}
