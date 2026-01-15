<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinic>
 */
class ClinicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'specialization_id' => null,
            'governorate_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => fake()->address(),
            'detailed_address' => fake()->sentence(),
            'floor' => fake()->numberBetween(1, 10),
            'room_number' => fake()->numberBetween(1, 100),
            'consultation_fee' => fake()->randomFloat(2, 20, 200),
            'description' => fake()->paragraph(),
            'username' => fake()->userName(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'main_image' => null,
            'working_hours' => json_encode([
                'saturday' => ['open' => '09:00', 'close' => '17:00'],
                'sunday' => ['open' => '09:00', 'close' => '17:00'],
                'monday' => ['open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['open' => '09:00', 'close' => '17:00'],
                'wednesday' => ['open' => '09:00', 'close' => '17:00'],
                'thursday' => ['open' => '09:00', 'close' => '17:00'],
                'friday' => ['off' => true]
            ]),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => 'approved',
            'otp_code' => null,
            'otp_expires_at' => null,
            'phone_verified_at' => now(),
        ];
    }
}
