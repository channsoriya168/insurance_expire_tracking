<?php

namespace App\Exports;

use App\Models\Insurance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements FromQuery<Insurance>
 */
final class InsurancesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<Insurance>  $query
     */
    public function __construct(private readonly Builder $query) {}

    /**
     * @return Builder<Insurance>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Insurance Company',
            'Policy No',
            'Contact Method',
            'Contact Name',
            'Contact Person',
            'Insured Name',
            'Expiry Date',
            'Policy Type',
            'Sum Insured',
            'Premium',
            'Net Premium',
            'Revised Sum Insured',
            'Revised Premium',
            'Revised Premium Rate',
            'Confirmed Date',
            'Status',
            'Payment Status',
            'Payment Date',
            'Policy Received Date',
            'Remarks',
        ];
    }

    /**
     * @param  Insurance  $insurance
     * @return list<mixed>
     */
    public function map($insurance): array
    {
        return [
            $insurance->insuranceCompany?->name,
            $insurance->policy_no,
            $insurance->contact_method?->value,
            $insurance->contact_value,
            $insurance->contact_person,
            $insurance->insured_name,
            $insurance->expiry_date?->format('Y-m-d'),
            $insurance->policyType?->name,
            $insurance->sum_insured,
            $insurance->premium,
            $insurance->net_premium,
            $insurance->revised_sum_insured,
            $insurance->revised_premium,
            $insurance->revised_premium_rate,
            $insurance->confirmed_date?->format('Y-m-d'),
            $insurance->status?->value,
            $insurance->payment_status?->value,
            $insurance->payment_date?->format('Y-m-d'),
            $insurance->policy_received_date?->format('Y-m-d'),
            $insurance->remarks,
        ];
    }
}
