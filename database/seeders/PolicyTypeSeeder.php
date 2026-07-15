<?php

namespace Database\Seeders;

use App\Models\PolicyType;
use Illuminate\Database\Seeder;

class PolicyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Motor', 'Fire', 'PAR', 'GPA', 'CAR', 'Medical'] as $name) {
            PolicyType::create(['name' => $name]);
        }
    }
}
