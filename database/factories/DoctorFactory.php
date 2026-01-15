<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->userName(),
            'license_number' => fake()->numerify('LIC####'),
            'consultation_price' => fake()->randomFloat(2, 50, 500),
            'practicing_profession_date' => fake()->numberBetween(2000, 2020),
            'governorate_id' => null,
            'city_id' => null,
            'district_id' => null,
            'area' => fake()->word(),
            'address_details' => fake()->address(),
            'bio' => fake()->sentence(),
            'distinguished_specialties' => fake()->words(3, true),
            'facebook_link' => fake()->url(),
            'instagram_link' => fake()->url(),
            'status' => 'approved',
            'phone_verified' => true,
            'has_secretary_service' => fake()->boolean()
        ];
    }
}
