<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Lonpac', 'Infinity', 'Philip'] as $name) {
            InsuranceCompany::create(['name' => $name]);
        }
    }
}
