<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\MedicalCenter;
use App\Models\Patient;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createPatientUser(): array
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'user_id' => $user->id,
        ]);

        return [$user, $patient];
    }

    /** @test */
    public function patient_can_create_review_for_doctor_after_completed_appointment()
    {
        [$user, $patient] = $this->createPatientUser();

        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $payload = [
            'target_id' => $doctor->id,
            'target_type' => 'doctor',
            'rating' => 5,
            'comment' => 'Great doctor',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reviews', $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'rating' => 5,
                'comment' => 'Great doctor',
            ]);

        $this->assertDatabaseHas('reviews', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'rating' => 5,
        ]);
    }

    /** @test */
    public function patient_cannot_create_review_without_completed_visit()
    {
        [$user, $patient] = $this->createPatientUser();

        $doctor = Doctor::factory()->create();

        $payload = [
            'target_id' => $doctor->id,
            'target_type' => 'doctor',
            'rating' => 4,
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/reviews', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'عذراً، يجب إتمام زيارة واحدة على الأقل لتتمكن من التقييم',
            ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    /** @test */
    public function check_eligibility_returns_true_when_patient_has_completed_visit_for_center()
    {
        [$user, $patient] = $this->createPatientUser();

        $center = MedicalCenter::factory()->create();
        $clinic = Clinic::factory()->create([
            'medical_center_id' => $center->id,
        ]);
        $doctor = Doctor::factory()->create();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reviews/check-eligibility?target_id=' . $center->id . '&target_type=center');

        $response->assertOk()
            ->assertJsonFragment([
                'can_review' => true,
            ]);
    }

    /** @test */
    public function stats_endpoint_returns_correct_aggregation()
    {
        [$user, $patient] = $this->createPatientUser();

        $clinic = Clinic::factory()->create();

        Review::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $clinic->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $clinic->id,
            'rating' => 3,
        ]);

        $response = $this->getJson('/api/v1/reviews/stats/clinic/' . $clinic->id);

        $response->assertOk()
            ->assertJsonFragment([
                'total_reviews' => 2,
            ]);

        $data = $response->json();

        $this->assertEquals(4.0, $data['average_rating']);
        $this->assertEquals(1, $data['breakdown']['5_stars']);
        $this->assertEquals(1, $data['breakdown']['3_stars']);
    }
}

