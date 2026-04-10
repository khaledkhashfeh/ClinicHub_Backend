<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOptionalPatient;
use App\Http\Controllers\Concerns\ResolvesPublicMediaUrls;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Services\ClinicOpenStatusService;
use App\Support\PublicContactLinks;
use App\Support\WorkingHoursApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicClinicController extends Controller
{
    use ResolvesOptionalPatient;
    use ResolvesPublicMediaUrls;

    public function __construct(
        private ClinicOpenStatusService $openStatus
    ) {}

    /**
     * GET /api/v1/clinics
     * يعمل مع أو بدون توكن (مع توكن مريض: is_favorite).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'governorate_id' => ['nullable', 'integer', 'min:1'],
            'gov_id' => ['nullable', 'integer', 'min:1'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'specialty_id' => ['nullable', 'integer', 'min:1'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_open' => ['nullable'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $governorateId = $validated['governorate_id'] ?? $validated['gov_id'] ?? null;
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 15);
        $limit = min(max($limit, 1), 50);

        $filterOpen = $request->has('is_open')
            ? filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $reviewStats = DB::table('reviews')
            ->select('clinic_id')
            ->selectRaw('AVG(rating) as avg_rating')
            ->selectRaw('COUNT(*) as cnt')
            ->whereNotNull('clinic_id')
            ->groupBy('clinic_id');

        $query = Clinic::query()
            ->select('clinics.*')
            ->selectRaw('COALESCE(review_stats.avg_rating, 0) as sort_avg_rating')
            ->selectRaw('COALESCE(review_stats.cnt, 0) as reviews_count_agg')
            ->leftJoinSub($reviewStats, 'review_stats', 'review_stats.clinic_id', '=', 'clinics.id')
            ->where('clinics.status', 'approved')
            ->with(['governorate', 'district', 'specialization']);

        if (!empty($validated['search'])) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $validated['search']) . '%';
            $query->where('clinics.clinic_name', 'like', $term);
        }

        if ($governorateId) {
            $query->where('clinics.governorate_id', $governorateId);
        }

        if (!empty($validated['area_id'])) {
            $query->where('clinics.district_id', $validated['area_id']);
        }

        if (!empty($validated['specialty_id'])) {
            $query->where('clinics.specialization_id', $validated['specialty_id']);
        }

        if (isset($validated['rating']) && $validated['rating'] !== null && $validated['rating'] !== '') {
            $min = (float) $validated['rating'];
            $query->whereRaw('COALESCE(review_stats.avg_rating, 0) >= ?', [$min]);
        }

        $query->orderByDesc('sort_avg_rating')
            ->orderBy('clinics.clinic_name');

        if ($filterOpen === true) {
            $ids = (clone $query)->pluck('clinics.id');
            $openIds = [];
            foreach ($ids->chunk(150) as $chunk) {
                $batch = Clinic::query()
                    ->whereIn('id', $chunk)
                    ->get(['id', 'working_hours']);
                foreach ($batch as $clinic) {
                    if ($this->openStatus->isOpenNow($clinic)) {
                        $openIds[] = $clinic->id;
                    }
                }
            }
            if ($openIds === []) {
                return response()->json([
                    'success' => true,
                    'results_count' => 0,
                    'data' => [],
                    'pagination' => [
                        'total_records' => 0,
                        'current_page' => $page,
                        'total_pages' => 0,
                        'limit' => $limit,
                        'next_page' => null,
                        'prev_page' => null,
                    ],
                ]);
            }
            $query->whereIn('clinics.id', $openIds);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $patient = $this->optionalPatient();

        $favoriteSet = [];
        if ($patient && $paginator->count() > 0) {
            $favoriteSet = DB::table('patient_clinic_favorites')
                ->where('patient_id', $patient->id)
                ->whereIn('clinic_id', $paginator->pluck('id'))
                ->pluck('clinic_id')
                ->flip()
                ->all();
        }

        $data = $paginator->getCollection()->map(function (Clinic $clinic) use ($patient, $favoriteSet) {
            $avg = (float) ($clinic->sort_avg_rating ?? 0);
            $count = (int) ($clinic->reviews_count_agg ?? 0);
            $open = $this->openStatus->resolve($clinic);

            return [
                'id' => $clinic->id,
                'name' => $clinic->clinic_name,
                'image_url' => $this->resolveImageUrl($clinic->main_image),
                'governorate' => optional($clinic->governorate)->name_ar,
                'area' => optional($clinic->district)->name_ar,
                'specialty' => optional($clinic->specialization)->name_ar,
                'bio' => $clinic->description,
                'average_rating' => round($avg, 2),
                'reviews_count' => $count,
                'is_favorite' => $patient ? isset($favoriteSet[$clinic->id]) : false,
                'is_open' => $open['is_open'],
                'status_text' => $open['status_text'],
                'location' => [
                    'lat' => $clinic->latitude !== null ? (float) $clinic->latitude : null,
                    'lng' => $clinic->longitude !== null ? (float) $clinic->longitude : null,
                ],
                'address_details' => $clinic->detailed_address ?? $clinic->address,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'results_count' => $data->count(),
            'data' => $data,
            'pagination' => [
                'total_records' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'limit' => $paginator->perPage(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'prev_page' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/clinics/{id}
     */
    public function show(int $id): JsonResponse
    {
        $clinic = Clinic::query()
            ->where('id', $id)
            ->where('status', 'approved')
            ->with([
                'governorate',
                'district',
                'specialization',
                'doctors' => function ($q) {
                    $q->where('doctors.status', 'approved')
                        ->orderBy('doctors.id')
                        ->with(['user', 'governorate', 'district']);
                },
            ])
            ->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'العيادة غير موجودة.',
            ], 404);
        }

        $reviewRow = DB::table('reviews')
            ->where('clinic_id', $clinic->id)
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating')
            ->selectRaw('COUNT(*) as cnt')
            ->first();

        $avg = (float) ($reviewRow->avg_rating ?? 0);
        $reviewsCount = (int) ($reviewRow->cnt ?? 0);

        $open = $this->openStatus->resolve($clinic);

        $patient = $this->optionalPatient();
        $isFavorite = $patient
            && DB::table('patient_clinic_favorites')
                ->where('patient_id', $patient->id)
                ->where('clinic_id', $clinic->id)
                ->exists();

        $doctorIds = $clinic->doctors->pluck('id')->all();
        $doctorAvgs = $doctorIds === []
            ? collect()
            : DB::table('reviews')
                ->whereIn('doctor_id', $doctorIds)
                ->groupBy('doctor_id')
                ->selectRaw('doctor_id, AVG(rating) as avg_rating')
                ->pluck('avg_rating', 'doctor_id');

        $specialties = [];
        if ($clinic->specialization) {
            $specialties[] = [
                'id' => $clinic->specialization->id,
                'name' => $clinic->specialization->name_ar,
            ];
        }

        $doctorsPayload = $clinic->doctors->map(function (Doctor $doctor) use ($doctorAvgs) {
            $u = $doctor->user;
            $dAvg = (float) ($doctorAvgs[$doctor->id] ?? 0);

            return [
                'id' => $doctor->id,
                'full_name' => $u
                    ? 'د. ' . trim($u->first_name . ' ' . $u->last_name)
                    : 'د.',
                'image_url' => $this->resolveImageUrl($doctor->image),
                'governorate' => optional($doctor->governorate)->name_ar,
                'area' => optional($doctor->district)->name_ar,
                'average_rating' => round($dAvg, 2),
            ];
        })->values()->all();

        $fee = $clinic->consultation_fee;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $clinic->id,
                'name' => $clinic->clinic_name,
                'image_url' => $this->resolveImageUrl($clinic->main_image),
                'bio' => $clinic->description,
                'governorate' => optional($clinic->governorate)->name_ar,
                'area' => optional($clinic->district)->name_ar,
                'detailed_address' => $clinic->detailed_address ?? $clinic->address,
                'location' => [
                    'lat' => $clinic->latitude !== null ? (float) $clinic->latitude : null,
                    'lng' => $clinic->longitude !== null ? (float) $clinic->longitude : null,
                ],
                'consultation_fee' => $fee !== null ? (float) $fee : null,
                'is_open' => $open['is_open'],
                'status_text' => $open['status_text'],
                'average_rating' => round($avg, 2),
                'reviews_count' => $reviewsCount,
                'is_favorite' => $isFavorite,
                'contact_links' => PublicContactLinks::forClinic($clinic),
                'specialties' => $specialties,
                'working_hours' => WorkingHoursApiFormatter::format($clinic->working_hours),
                'doctors' => $doctorsPayload,
            ],
        ]);
    }
}
