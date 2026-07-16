<?php

namespace Database\Factories;

use App\Models\Insurance;
use App\Models\InsuranceCompany;
use App\Models\PolicyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insurance>
 */
class InsuranceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'insurance_company_id' => fn () => InsuranceCompany::inRandomOrder()->value('id') ?? InsuranceCompany::factory()->create()->id,
            'policy_no' => strtoupper(fake()->unique()->bothify('Y##??########')),
            'contact_method' => fake()->randomElement(['Email', 'WhatsApp', 'WeChat', 'Telegram']),
            'contact_value' => fake()->safeEmail(),
            'contact_person' => fake()->name(),
            'insured_name' => fake()->company(),
            'expiry_date' => fake()->dateTimeBetween('-30 days', '+60 days'),
            'policy_type_id' => fn () => PolicyType::inRandomOrder()->value('id') ?? PolicyType::factory()->create()->id,
            'sum_insured' => fake()->randomFloat(2, 1000, 500000),
            'premium' => fake()->randomFloat(2, 50, 20000),
            'net_premium' => fake()->randomFloat(2, 50, 20000),
            'revised_sum_insured' => fake()->randomFloat(2, 1000, 500000),
            'revised_premium' => fake()->randomFloat(2, 50, 20000),
            'revised_premium_rate' => fake()->randomFloat(3, 0, 10),
            'confirmed_date' => null,
            'status' => 'Pending',
            'payment_status' => 'Unpaid',
            'payment_date' => null,
            'policy_received_date' => null,
            'remarks' => null,
        ];
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn (): array => [
            'expiry_date' => today()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expiry_date' => today()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }
}
