<?php

namespace Database\Factories;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramAccessRequest>
 */
class TelegramAccessRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => fake()->unique()->numberBetween(100000, 999999),
            'first_name' => fake()->firstName(),
            'username' => fake()->userName(),
            'status' => TelegramAccessStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => TelegramAccessStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => TelegramAccessStatus::Rejected]);
    }
}
