<?php

namespace App\Imports;

use App\Enums\ContactMethod;
use App\Enums\PolicyStatus;
use App\Models\Insurance;
use App\Models\InsuranceCompany;
use App\Models\PolicyType;
use App\Services\InsuranceNotificationService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final class InsurancesImport implements ToCollection
{
    /**
     * Policy Type abbreviations as used by our insurance companies' source
     * files, mapped to the Policy Type name they should resolve to. More
     * abbreviations get added here as new data comes in.
     *
     * @var array<string, string>
     */
    private const POLICY_TYPE_ABBREVIATIONS = [
        'PG' => 'Medical',
        'HG' => 'Medical',
        'VP' => 'Medical',
        'VC' => 'Medical',
        'EB' => 'Medical',
        'GM' => 'Medical',
        'FF' => 'Fire',
        'PAR' => 'Fire',
        'EE' => 'CAR',
        'MM' => 'Marine Insurance',
        'GB' => 'Group Burglary / Group Business (General Liability / Theft)',
        'LL' => 'Legal Liability / Public Liability',
    ];

    private const DEFAULT_COMPANY_NAME = 'Lonpac';

    private const UNKNOWN_POLICY_TYPE = 'Unknown';

    private const DEFAULT_CONTACT_VALUE = 'N/A';

    private int $imported = 0;

    private int $skippedMissingPolicyNo = 0;

    private int $skippedMissingExpiryDate = 0;

    private int $skippedDuplicate = 0;

    private int $companiesCreated = 0;

    private int $policyTypesCreated = 0;

    /** @var array<string, InsuranceCompany> */
    private array $companyCache = [];

    /** @var array<string, PolicyType> */
    private array $policyTypeCache = [];

    /** @var array<string, true> */
    private array $knownPolicyNumbers = [];

    public function __construct(private readonly InsuranceNotificationService $notifications) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headerMap = $this->mapHeaders($rows->first());
        $dataRows = $rows->skip(1);

        $this->preloadExistingPolicyNumbers($dataRows, $headerMap);

        Insurance::withoutEvents(function () use ($dataRows, $headerMap): void {
            DB::transaction(function () use ($dataRows, $headerMap): void {
                foreach ($dataRows as $row) {
                    $this->importRow($row, $headerMap);
                }
            });
        });

        if ($this->imported > 0) {
            $this->notifications->syncAllNotifications();
        }
    }

    /**
     * One query to find which Policy Nos already exist, instead of one
     * `exists()` query per row — this is the main cost for a large file.
     *
     * @param  Collection<int, Collection<int, mixed>>  $dataRows
     * @param  array<string, int>  $headerMap
     */
    private function preloadExistingPolicyNumbers(Collection $dataRows, array $headerMap): void
    {
        if (! isset($headerMap['policy_no'])) {
            return;
        }

        $policyNumbers = $dataRows
            ->map(fn (Collection $row) => $this->cleanString($row->get($headerMap['policy_no'])))
            ->filter()
            ->unique()
            ->values();

        if ($policyNumbers->isEmpty()) {
            return;
        }

        $this->knownPolicyNumbers = Insurance::query()
            ->whereIn('policy_no', $policyNumbers)
            ->pluck('policy_no')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    public function summary(): string
    {
        $parts = ['Imported '.$this->imported.' polic'.($this->imported === 1 ? 'y' : 'ies').'.'];

        $skippedTotal = $this->skippedMissingPolicyNo + $this->skippedMissingExpiryDate + $this->skippedDuplicate;

        if ($skippedTotal > 0) {
            $reasons = array_filter([
                $this->skippedMissingPolicyNo > 0 ? "{$this->skippedMissingPolicyNo} missing Policy No." : null,
                $this->skippedMissingExpiryDate > 0 ? "{$this->skippedMissingExpiryDate} missing Expiry Date" : null,
                $this->skippedDuplicate > 0 ? "{$this->skippedDuplicate} duplicate Policy No." : null,
            ]);

            $parts[] = "Skipped {$skippedTotal} (".implode(', ', $reasons).').';
        }

        if ($this->companiesCreated > 0) {
            $parts[] = 'Created '.$this->companiesCreated.' new compan'.($this->companiesCreated === 1 ? 'y' : 'ies').'.';
        }

        if ($this->policyTypesCreated > 0) {
            $parts[] = 'Created '.$this->policyTypesCreated.' new policy type'.($this->policyTypesCreated === 1 ? '' : 's').'.';
        }

        return implode(' ', $parts);
    }

    /**
     * Normalizes each header cell and assigns it to the field it represents.
     * Matched by keyword rather than exact text, since different source
     * files spell the same column slightly differently (e.g. "EXPIRY DATE"
     * vs "EXPIRY dd/mmm/yyyy").
     *
     * @param  Collection<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function mapHeaders(Collection $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $value) {
            $header = $this->normalizeHeader($value);

            if ($header === '') {
                continue;
            }

            $field = match (true) {
                str_contains($header, 'INSURED NAME') => 'insured_name',
                str_contains($header, 'POLICY TYPE') => 'policy_type',
                str_contains($header, 'CONTACT PERSON') => 'contact_person',
                str_contains($header, 'CONTACT METHOD') => 'contact_method',
                str_contains($header, 'CONTACT NAME') => 'contact_value',
                str_contains($header, 'INS COMPANY'), str_contains($header, 'COMPANY') => 'company',
                str_contains($header, 'POLICY NO') => 'policy_no',
                str_contains($header, 'EXPIRY') => 'expiry_date',
                str_contains($header, 'REVISED SUM') => 'revised_sum_insured',
                str_contains($header, 'REVISED PRE') && str_contains($header, 'RATE') => 'revised_premium_rate',
                str_contains($header, 'REVISED PRE') => 'revised_premium',
                str_contains($header, 'NET PRE') => 'net_premium',
                str_contains($header, 'SUM INSURED') => 'sum_insured',
                $header === 'PRE' => 'premium',
                str_contains($header, 'CONFIRMED') => 'confirmed_date',
                $header === 'STATUS' => 'status',
                str_contains($header, 'PAYMENT DATE') => 'payment_date',
                str_contains($header, 'RECEIVED DATE') => 'policy_received_date',
                str_contains($header, 'REMARKS') => 'remarks',
                default => null,
            };

            if ($field !== null) {
                $map[$field] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  Collection<int, mixed>  $row
     * @param  array<string, int>  $headerMap
     */
    private function importRow(Collection $row, array $headerMap): void
    {
        $get = fn (string $field): mixed => isset($headerMap[$field]) ? $row->get($headerMap[$field]) : null;

        $insuredName = $this->cleanString($get('insured_name'));

        if ($insuredName === null) {
            return;
        }

        $policyNo = $this->cleanString($get('policy_no'));

        if ($policyNo === null) {
            $this->skippedMissingPolicyNo++;

            return;
        }

        if (isset($this->knownPolicyNumbers[$policyNo])) {
            $this->skippedDuplicate++;

            return;
        }

        $expiryDate = $this->parseDate($get('expiry_date'));

        if ($expiryDate === null) {
            $this->skippedMissingExpiryDate++;

            return;
        }

        $sumInsured = $this->parseDecimal($get('sum_insured')) ?? 0.0;
        $premium = $this->parseDecimal($get('premium')) ?? 0.0;
        $revisedSumInsured = $this->parseDecimal($get('revised_sum_insured')) ?? $sumInsured;
        $revisedPremium = $this->parseDecimal($get('revised_premium')) ?? $premium;
        $revisedPremiumRate = $revisedSumInsured > 0 ? round($revisedPremium / $revisedSumInsured, 4) : 0.0;
        $netPremium = round($revisedPremium * 0.85, 2);

        Insurance::create([
            'insurance_company_id' => $this->resolveCompany($this->cleanString($get('company')))->id,
            'policy_no' => $policyNo,
            'contact_method' => $this->resolveContactMethod($this->cleanString($get('contact_method'))),
            'contact_value' => $this->cleanString($get('contact_value')) ?? self::DEFAULT_CONTACT_VALUE,
            'contact_person' => $this->cleanString($get('contact_person')) ?? self::DEFAULT_CONTACT_VALUE,
            'insured_name' => $insuredName,
            'expiry_date' => $expiryDate,
            'policy_type_id' => $this->resolvePolicyType($this->cleanString($get('policy_type')))->id,
            'sum_insured' => $sumInsured,
            'premium' => $premium,
            'net_premium' => $netPremium,
            'revised_sum_insured' => $revisedSumInsured,
            'revised_premium' => $revisedPremium,
            'revised_premium_rate' => $revisedPremiumRate,
            'confirmed_date' => $this->parseDate($get('confirmed_date')),
            'status' => $this->resolveStatus($this->cleanString($get('status'))),
            'payment_status' => 'Unpaid',
            'payment_date' => $this->parseDate($get('payment_date')),
            'policy_received_date' => $this->parseDate($get('policy_received_date')),
            'remarks' => $this->cleanString($get('remarks')),
        ]);

        $this->knownPolicyNumbers[$policyNo] = true;
        $this->imported++;
    }

    private function resolveCompany(?string $raw): InsuranceCompany
    {
        return $this->findOrCreateCompany($raw ?? self::DEFAULT_COMPANY_NAME);
    }

    private function findOrCreateCompany(string $name): InsuranceCompany
    {
        $cacheKey = mb_strtolower($name);

        if (isset($this->companyCache[$cacheKey])) {
            return $this->companyCache[$cacheKey];
        }

        $existing = InsuranceCompany::query()->whereRaw('LOWER(name) = ?', [$cacheKey])->first();

        if ($existing !== null) {
            return $this->companyCache[$cacheKey] = $existing;
        }

        $this->companiesCreated++;

        return $this->companyCache[$cacheKey] = InsuranceCompany::create(['name' => $name]);
    }

    private function resolvePolicyType(?string $raw): PolicyType
    {
        if ($raw !== null) {
            $upper = mb_strtoupper($raw);

            if (isset(self::POLICY_TYPE_ABBREVIATIONS[$upper])) {
                return $this->findOrCreatePolicyType(self::POLICY_TYPE_ABBREVIATIONS[$upper]);
            }

            $existing = PolicyType::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($raw)])->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->findOrCreatePolicyType(self::UNKNOWN_POLICY_TYPE);
    }

    private function findOrCreatePolicyType(string $name): PolicyType
    {
        $cacheKey = mb_strtolower($name);

        if (isset($this->policyTypeCache[$cacheKey])) {
            return $this->policyTypeCache[$cacheKey];
        }

        $existing = PolicyType::query()->whereRaw('LOWER(name) = ?', [$cacheKey])->first();

        if ($existing !== null) {
            return $this->policyTypeCache[$cacheKey] = $existing;
        }

        $this->policyTypesCreated++;

        return $this->policyTypeCache[$cacheKey] = PolicyType::create(['name' => $name]);
    }

    private function resolveContactMethod(?string $raw): string
    {
        if ($raw !== null) {
            foreach (ContactMethod::cases() as $case) {
                if (strcasecmp($case->value, $raw) === 0) {
                    return $case->value;
                }
            }
        }

        return ContactMethod::Phone->value;
    }

    private function resolveStatus(?string $raw): string
    {
        if ($raw !== null) {
            foreach (PolicyStatus::cases() as $case) {
                if (strcasecmp($case->value, $raw) === 0) {
                    return $case->value;
                }
            }
        }

        return PolicyStatus::Pending->value;
    }

    private function normalizeHeader(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $normalized === '' || $normalized === null ? null : (float) $normalized;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
