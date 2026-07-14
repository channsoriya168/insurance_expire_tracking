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
        Insurance::factory()->count(5)->expired()->create();
        Insurance::factory()->count(3)->expiringInDays(10)->create();
        Insurance::factory()->count(3)->expiringInDays(20)->create();
        Insurance::factory()->count(3)->expiringInDays(30)->create();
        Insurance::factory()->count(36)->create();

        // DatabaseSeeder disables model events, so the InsuranceObserver
        // never fires here; sync notifications for the seeded policies directly.
        $notifications->syncAllNotifications();
    }
}
