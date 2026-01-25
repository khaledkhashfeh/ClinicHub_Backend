<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Invitation;
use App\Models\UserFcmToken;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{
    private $pushNotificationService;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    /**
     * Search and list available doctors
     */
    public function availableDoctors(Request $request): JsonResponse
    {
        $query = $request->input('query');

        $doctorsQuery = Doctor::with(['user', 'governorate', 'city'])
            ->whereHas('user', function ($q) {
                $q->where('status', 'approved');
            })
            ->where('status', 'approved');

        if ($query) {
            $doctorsQuery->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'LIKE', "%{$query}%")
                              ->orWhere('last_name', 'LIKE', "%{$query}%")
                              ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->orWhere('username', 'LIKE', "%{$query}%")
                ->orWhereHas('specializations', function ($specQuery) use ($query) {
                    $specQuery->where('name_ar', 'LIKE', "%{$query}%")
                             ->orWhere('name_en', 'LIKE', "%{$query}%");
                });
            });
        }

        $doctors = $doctorsQuery->paginate(15);

        $formattedDoctors = $doctors->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'email' => $doctor->user->email,
                'phone' => $doctor->user->phone,
                'specialization' => $doctor->specializations->pluck('name')->join(', ') ?: 'غير محدد',
                'profile_image' => $doctor->image ?? null,
                'governorate' => $doctor->governorate ? [
                    'id' => $doctor->governorate->id,
                    'name' => $doctor->governorate->name
                ] : null,
                'city' => $doctor->city ? [
                    'id' => $doctor->city->id,
                    'name' => $doctor->city->name
                ] : null,
                'years_of_experience' => $doctor->practicing_profession_date ?
                    now()->year - $doctor->practicing_profession_date : 0
            ];
        });

        return response()->json([
            'status' => 'success',
            'total_count' => $doctors->total(),
            'data' => $formattedDoctors,
            'links' => [
                'first' => $doctors->url(1),
                'last' => $doctors->url($doctors->lastPage()),
                'prev' => $doctors->previousPageUrl(),
                'next' => $doctors->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'from' => $doctors->firstItem(),
                'last_page' => $doctors->lastPage(),
                'path' => $doctors->path(),
                'per_page' => $doctors->perPage(),
                'to' => $doctors->lastItem(),
                'total' => $doctors->total(),
            ]
        ]);
    }

    /**
     * Send job invitation to a doctor
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'clinic_id' => 'required|exists:clinics,id',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Check if doctor exists and is approved
            $doctor = Doctor::with('user')->find($request->doctor_id);
            if (!$doctor || $doctor->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'الطبيب المحدد غير موجود أو غير معتمد'
                ], 404);
            }

            // Check if clinic exists
            $clinic = Clinic::find($request->clinic_id);
            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'العيادة المحددة غير موجودة'
                ], 404);
            }

            // Check if an invitation already exists between this doctor and clinic
            $existingInvitation = Invitation::where('doctor_id', $request->doctor_id)
                ->where('clinic_id', $request->clinic_id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();

            if ($existingInvitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد أرسلت دعوة سابقة لهذا الطبيب'
                ], 400);
            }

            // Create the invitation
            $invitation = Invitation::create([
                'doctor_id' => $request->doctor_id,
                'clinic_id' => $request->clinic_id,
                'message' => $request->message ?? "دعوة للانضمام إلى الطاقم الطبي في {$clinic->clinic_name}",
                'status' => 'pending'
            ]);

            // Send push notification to the doctor
            $this->pushNotificationService->sendToDoctor(
                $doctor->id,
                'فرصة انضمام جديدة 🏥',
                "ترغب عيادة {$clinic->clinic_name} في دعوتك للانضمام إلى فريقها الطبي."
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الدعوة بنجاح',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'status' => $invitation->status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending invitation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الدعوة'
            ], 500);
        }
    }

    /**
     * Respond to a job invitation
     */
    public function respondToInvitation(Request $request, $invitationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        try {
            $invitation = Invitation::with(['doctor.user', 'clinic'])->find($invitationId);

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'الدعوة غير موجودة'
                ], 404);
            }

            // Check if the authenticated doctor is the one who received the invitation
            $authenticatedDoctor = auth()->user()->doctor;
            if ($invitation->doctor_id !== $authenticatedDoctor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالرد على هذه الدعوة'
                ], 403);
            }

            // Check if invitation has already been responded to
            if ($invitation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'تم الرد على هذه الدعوة مسبقاً'
                ], 400);
            }

            DB::beginTransaction();

            // Update the invitation status and response time
            $invitation->update([
                'status' => $request->status,
                'responded_at' => now()
            ]);

            if ($request->status === 'accepted') {
                // Add doctor to clinic's team
                $clinic = $invitation->clinic;
                $doctor = $invitation->doctor;

                // Check if doctor is already associated with this clinic
                $existingAssociation = DB::table('clinic_doctor')
                    ->where('clinic_id', $clinic->id)
                    ->where('doctor_id', $doctor->id)
                    ->first();

                if (!$existingAssociation) {
                    DB::table('clinic_doctor')->insert([
                        'clinic_id' => $clinic->id,
                        'doctor_id' => $doctor->id,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Send notification to clinic admin
                $this->pushNotificationService->sendToClinic(
                    $clinic->id,
                    'تم قبول الدعوة ✅',
                    "تم قبول الدعوة: انضم الدكتور {$doctor->full_name} رسمياً لعيادتكم."
                );
            } elseif ($request->status === 'rejected') {
                // Send notification to clinic admin about rejection
                $clinic = $invitation->clinic;
                $doctor = $invitation->doctor;

                $this->pushNotificationService->sendToClinic(
                    $clinic->id,
                    'تحديث الدعوة ❌',
                    "تحديث الدعوة ❌: اعتذر الدكتور {$doctor->full_name} عن الانضمام حالياً."
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الدعوة بنجاح',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'status' => $invitation->status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error responding to invitation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الدعوة'
            ], 500);
        }
    }

    /**
     * Get invitations sent by the authenticated clinic
     */
    public function getClinicInvitations(Request $request): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - must be authenticated as clinic'
                ], 403);
            }

            $status = $request->query('status'); // Optional filter by status

            $query = \App\Models\Invitation::with(['doctor.user', 'clinic'])
                ->where('clinic_id', $clinic->id);

            if ($status) {
                $query->where('status', $status);
            }

            $invitations = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $invitations
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting clinic invitations: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invitations',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(Request $request, $invitationId): JsonResponse
    {
        try {
            $clinic = auth()->guard('clinic')->user();

            if (!$clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access - must be authenticated as clinic'
                ], 403);
            }

            $invitation = \App\Models\Invitation::where('id', $invitationId)
                ->where('clinic_id', $clinic->id)
                ->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invitation not found or does not belong to this clinic'
                ], 404);
            }

            // Cannot cancel if already accepted/rejected
            if (!in_array($invitation->status, ['pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel invitation - it has already been ' . $invitation->status
                ], 400);
            }

            // Update status to cancelled
            $invitation->update([
                'status' => 'cancelled',
                'responded_at' => now()
            ]);

            // Send notification to doctor about cancellation
            $this->pushNotificationService->sendToDoctor(
                $invitation->doctor_id,
                'تم إلغاء الدعوة 📭',
                "لقد تم إلغاء دعوة العمل من قبل {$clinic->clinic_name}."
            );

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'status' => $invitation->status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling invitation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error cancelling invitation',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}
