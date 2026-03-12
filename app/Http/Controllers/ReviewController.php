<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\CreateReviewRequest;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\MedicalCenter;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Create a new review for doctor / clinic / center.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->patient) {
            return response()->json([
                'message' => 'Only patients can create reviews',
            ], 403);
        }

        $patient = $user->patient;

        $targetId = (int) $request->input('target_id');
        $targetType = $request->input('target_type');

        if (!$this->hasCompletedVisit($patient->id, $targetType, $targetId)) {
            return response()->json([
                'message' => 'عذراً، يجب إتمام زيارة واحدة على الأقل لتتمكن من التقييم',
            ], 403);
        }

        $reviewData = [
            'patient_id' => $patient->id,
            'doctor_id' => null,
            'clinic_id' => null,
            'medical_center_id' => null,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ];

        if ($targetType === 'doctor') {
            $reviewData['doctor_id'] = $targetId;
        } elseif ($targetType === 'clinic') {
            $reviewData['clinic_id'] = $targetId;
        } elseif ($targetType === 'center') {
            $reviewData['medical_center_id'] = $targetId;
        }

        $review = Review::create($reviewData);

        return response()->json([
            'message' => 'Review created successfully',
            'data' => $review,
        ], 201);
    }

    /**
     * Check if authenticated patient can review target.
     */
    public function checkEligibility(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->patient) {
            return response()->json([
                'can_review' => false,
                'message' => 'Only patients can review entities.',
            ], 403);
        }

        $patient = $user->patient;

        $request->validate([
            'target_id' => ['required', 'integer', 'min:1'],
            'target_type' => ['required', 'string', 'in:doctor,clinic,center'],
        ], [
            'target_id.required' => 'target_id is required.',
            'target_type.required' => 'target_type is required.',
            'target_type.in' => 'target_type must be doctor, clinic, or center.',
        ]);

        $targetId = (int) $request->query('target_id');
        $targetType = $request->query('target_type');

        $canReview = $this->hasCompletedVisit($patient->id, $targetType, $targetId);

        return response()->json([
            'can_review' => $canReview,
            'message' => $canReview
                ? 'You are eligible to review this ' . $targetType . '.'
                : 'You are not eligible to review this ' . $targetType . '.',
        ]);
    }

    /**
     * Get review statistics for a doctor / clinic / center.
     */
    public function stats(string $targetType, int $targetId): JsonResponse
    {
        if (!in_array($targetType, ['doctor', 'clinic', 'center'], true)) {
            return response()->json([
                'message' => 'Invalid target type.',
            ], 422);
        }

        if ($targetId < 1) {
            return response()->json([
                'message' => 'Invalid target id.',
            ], 422);
        }

        $query = Review::query();

        if ($targetType === 'doctor') {
            $query->where('doctor_id', $targetId);
        } elseif ($targetType === 'clinic') {
            $query->where('clinic_id', $targetId);
        } elseif ($targetType === 'center') {
            $query->where('medical_center_id', $targetId);
        }

        $reviews = $query->get();

        $total = $reviews->count();

        if ($total === 0) {
            return response()->json([
                'average_rating' => 0,
                'total_reviews' => 0,
                'breakdown' => [
                    '5_stars' => 0,
                    '4_stars' => 0,
                    '3_stars' => 0,
                    '2_stars' => 0,
                    '1_star' => 0,
                ],
            ]);
        }

        $avgValue = $reviews->avg('rating');
        $average = $avgValue !== null ? round((float) $avgValue, 2) : 0;

        $breakdown = [
            '5_stars' => $reviews->where('rating', 5)->count(),
            '4_stars' => $reviews->where('rating', 4)->count(),
            '3_stars' => $reviews->where('rating', 3)->count(),
            '2_stars' => $reviews->where('rating', 2)->count(),
            '1_star' => $reviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'average_rating' => $average,
            'total_reviews' => $total,
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Determine if patient has at least one completed appointment
     * with the given target.
     */
    private function hasCompletedVisit(int $patientId, string $targetType, int $targetId): bool
    {
        $appointments = Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', Appointment::STATUS_COMPLETED);

        if ($targetType === 'doctor') {
            $appointments->where('doctor_id', $targetId);
        } elseif ($targetType === 'clinic') {
            $appointments->where('clinic_id', $targetId);
        } elseif ($targetType === 'center') {
            $center = MedicalCenter::find($targetId);

            if (!$center) {
                return false;
            }

            $clinicIds = $center->clinics()->pluck('id');

            if ($clinicIds->isEmpty()) {
                return false;
            }

            $appointments->whereIn('clinic_id', $clinicIds);
        }

        return $appointments->exists();
    }
}

