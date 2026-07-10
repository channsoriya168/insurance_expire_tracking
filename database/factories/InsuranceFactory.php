<?php

namespace Database\Factories;

use App\Models\Insurance;
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
            'insurance_company' => fake()->randomElement(['Lonpac', 'Infinity', 'Philip']),
            'policy_no' => strtoupper(fake()->unique()->bothify('Y##??########')),
            'contact_method' => fake()->randomElement(['Email', 'WhatsApp', 'WeChat', 'Telegram']),
            'contact_value' => fake()->safeEmail(),
            'contact_person' => fake()->name(),
            'insured_name' => fake()->company(),
            'expiry_date' => fake()->dateTimeBetween('-30 days', '+60 days'),
            'policy_type' => fake()->randomElement(['Fire', 'PAR', 'GPA', 'Motor Ins', 'CAR']),
            'sum_insured' => fake()->randomFloat(2, 1000, 500000),
            'premium' => fake()->randomFloat(2, 50, 20000),
            'revised_sum_insured' => null,
            'revised_premium' => null,
            'revised_premium_rate' => null,
            'confirmed_date' => null,
            'status' => 'Pending',
            'request_policy_date' => null,
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
