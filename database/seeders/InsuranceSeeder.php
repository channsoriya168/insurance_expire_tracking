<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Services\InsuranceNotificationService;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(InsuranceNotificationService $notifications): void
    {
        $columns = [
            'insurance_company', 'policy_no', 'contact_method', 'contact_value', 'contact_person',
            'insured_name', 'expiry_date', 'policy_type', 'sum_insured', 'premium',
            'revised_sum_insured', 'revised_premium', 'revised_premium_rate', 'confirmed_date',
            'status', 'request_policy_date', 'policy_received_date', 'remarks',
        ];

        foreach ($this->records() as $record) {
            Insurance::create(array_combine($columns, $record));
        }

        // DatabaseSeeder disables model events, so the InsuranceObserver
        // never fires here; sync notifications for the seeded policies directly.
        $notifications->syncAllNotifications();
    }

    /**
     * @return list<list<mixed>>
     */
    private function records(): array
    {
        return [
            ['Lonpac', 'Y25VC31008507', 'Email', 'heng@itsolutiondigital.com', null, 'SIGLO (CAMBODIA) CO., LTD.', '2025-07-16', 'Motor Ins', null, '561.20', '200000.00', '500.20', '0.180', '2026-10-12', 'Pending', '2025-10-12', null, null],
            ['Infinity', 'Y25FA00085063', 'WhatsApp', '85517868883', null, 'KING SG APPAREL (CAMBODIA)', '2025-07-16', 'Fire', null, '9690', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y25VP00008449', 'WeChat', 'Can be name or num', null, 'LUO MIN', '2025-07-19', 'PAR', null, '536.75', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008417', 'Telegram', 'Can be name or num', null, 'YANG QIANWEI', '2025-07-19', 'GPA', null, '298.1', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008492', null, null, null, 'CASWELL APPAREL CO., LTD.', '2025-07-25', 'CAR', null, '374.85', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008436', null, null, null, 'HIN SOKHA', '2025-07-25', null, null, '135.5', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008468', null, null, null, 'LI PO CHUANG', '2025-07-29', null, null, '414.40', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008440', null, null, null, 'DU XUNMEI', '2025-07-29', null, null, '2160.25', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VC00008519', null, null, null, 'OLIVE APPAREL (CAMBODIA) CO., LTD.', '2025-07-31', null, null, '3984.00', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25GM00002168', null, null, null, 'EASTEX GARMENT CO., LTD.', '2025-07-31', null, null, '1258', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008481', null, null, null, 'YUEN SEREYRATH', null, null, null, '616.1', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VP00008482', null, null, null, 'YUEN SEREYRATH', null, null, null, '207.7', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25FA00085062', null, null, null, 'NEW FOCUS APPAREL', null, null, null, '9690', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25FA00085089', null, null, null, 'PHNOM PENH TALENT SOCKS', null, null, null, '17050', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VC00008445', null, null, null, 'VANCO INDUSTRIAL CO., LTD.', null, null, null, '274.8', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25VC00008446', null, null, null, 'VANCO INDUSTRIAL CO., LTD.', null, null, null, '467.6', null, null, null, null, 'Pending', null, null, null],
            [null, 'Y25GM00002167', null, null, null, 'VANCO INDUSTRIAL CO., LTD.', null, null, null, '1258', null, null, null, null, 'Pending', null, null, null],
        ];
    }
}
