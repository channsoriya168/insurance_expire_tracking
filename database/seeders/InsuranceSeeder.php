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
            ['Infinity', 'Y89EB07384990', 'Email', 'thurman69@example.com', 'Graciela Kuhic', 'Schmitt, Block and Hermiston', '2026-07-02', 'PAR', '344698.69', '10481.41', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y52LS97218655', 'Email', 'corkery.karen@example.com', 'Prof. Stefan Jones', 'Kunze, Gutmann and Mraz', '2026-07-03', 'GPA', '138018.36', '11741.56', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y44MY03853787', 'Email', 'gaylord.emely@example.com', 'Alva Kilback DVM', 'Cartwright-Tillman', '2026-07-06', 'GPA', '355840.79', '2673.31', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y05KD81781883', 'WeChat', 'breitenberg.leonard@example.com', 'Trent Little', 'Pfeffer and Sons', '2026-07-09', 'Motor Ins', '476366.64', '2166.02', null, null, null, null, 'Pending', null, null, null],
            ['Infinity', 'Y01ZU23109922', 'Telegram', 'nicolas.lizeth@example.org', 'Naomie Spinka', 'Hartmann-Stamm', '2026-07-09', 'Fire', '390921.68', '3584.02', null, null, null, null, 'Pending', null, null, null],
            ['Infinity', 'Y34KH16846000', 'Telegram', 'pswift@example.net', 'Avis Bartell', 'Jenkins Group', '2026-07-12', 'Fire', '395237.06', '13191.3', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y39ZF56073379', 'WeChat', 'manuela50@example.net', 'Joaquin Eichmann', 'Gleichner, Dooley and Senger', '2026-07-13', 'CAR', '87482.41', '4694.65', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y38MK66681562', 'WhatsApp', 'sstracke@example.net', "Eliseo O'Keefe", 'Bashirian, Klocko and Schmitt', '2026-07-14', 'GPA', '256653.87', '19906.4', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y22FJ48802468', 'Email', 'bharris@example.com', 'Prof. Verla Cummings IV', 'Corwin, Predovic and Powlowski', '2026-07-18', 'Motor Ins', '98107.61', '13065.74', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y89ZR48980144', 'Email', 'block.halle@example.org', 'Brisa Bailey II', 'Prohaska PLC', '2026-07-18', 'Motor Ins', '440155.05', '18770.12', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y96YS60407135', 'WhatsApp', 'hudson.amir@example.org', 'Andres Pacocha', 'Durgan-Marks', '2026-07-24', 'CAR', '6728.91', '1572.07', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y31KE89788251', 'WeChat', 'casper17@example.net', 'Jeremy Bernhard', 'Roberts, Stamm and Huel', '2026-07-24', 'Motor Ins', '319351.02', '5866.9', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y57CU18654058', 'WeChat', 'johnathan88@example.com', 'Kathryne Nolan', 'Koss PLC', '2026-07-24', 'GPA', '266928.98', '3213.2', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y98CZ40360493', 'WeChat', 'atowne@example.net', 'Lea Heaney IV', 'Hagenes Group', '2026-07-26', 'Fire', '397045.17', '10217.19', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y01YG87602488', 'WeChat', 'dalton96@example.net', 'Julie Ledner', 'Quitzon, Muller and Kertzmann', '2026-07-28', 'Motor Ins', '196248.91', '19702.77', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y61OX42238708', 'WhatsApp', 'waldo27@example.com', 'Madonna Rath', 'Reilly and Sons', '2026-07-29', 'GPA', '345394.2', '9607.49', null, null, null, null, 'Pending', null, null, null],
            ['Lonpac', 'Y18GJ56986175', 'WeChat', 'aubrey.orn@example.org', 'Winona Davis', 'Schoen Ltd', '2026-07-30', 'Fire', '132433.38', '12785.02', null, null, null, null, 'Pending', null, null, null],
            ['Philip', 'Y62MT81385642', 'WeChat', 'keebler.lambert@example.net', 'Nicholas Ruecker', 'Moen-Shanahan', '2026-07-31', 'GPA', '115682.86', '7864.72', null, null, null, null, 'Pending', null, null, null],
        ];
    }
}
