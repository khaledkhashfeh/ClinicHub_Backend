<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SpecializationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => $this->faker->word,
            'name_en' => $this->faker->word,
            'icon' => $this->faker->word,
            'is_active' => true,
        ];
    }
}
