<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Insurance::factory()->count(5)->expired()->create();
        Insurance::factory()->count(3)->expiringInDays(10)->create();
        Insurance::factory()->count(3)->expiringInDays(20)->create();
        Insurance::factory()->count(3)->expiringInDays(30)->create();
        Insurance::factory()->count(36)->create();
    }
}
