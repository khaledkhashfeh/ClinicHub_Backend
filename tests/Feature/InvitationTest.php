<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_doctors_endpoint_exists()
    {
        $response = $this->get('/api/available-doctors');

        $response->assertStatus(200);
    }

    public function test_available_doctors_endpoint_with_query_exists()
    {
        $response = $this->get('/api/available-doctors?query=test');

        $response->assertStatus(200);
    }

    public function test_send_invitation_requires_authentication()
    {
        $response = $this->postJson('/api/clinics/invitations/send', [
            'doctor_id' => 1,
            'clinic_id' => 1,
            'message' => 'Test invitation'
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    public function test_respond_to_invitation_requires_authentication()
    {
        $response = $this->patchJson('/api/doctor/invitations/1', [
            'status' => 'accepted'
        ]);

        $response->assertStatus(401); // Unauthorized
    }
}
