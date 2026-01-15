<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GovernorateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => $this->faker->city,
            'name_en' => $this->faker->city,
        ];
    }
}
