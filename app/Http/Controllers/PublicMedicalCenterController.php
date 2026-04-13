<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOptionalPatient;
use App\Http\Controllers\Concerns\ResolvesPublicMediaUrls;
use App\Models\Clinic;
use App\Models\MedicalCenter;
use App\Models\Specialization;
use App\Services\ClinicOpenStatusService;
use App\Support\PublicContactLinks;
use App\Support\WorkingHoursApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicMedicalCenterController extends Controller
{
    use ResolvesOptionalPatient;
    use ResolvesPublicMediaUrls;

    public function __construct(
        private ClinicOpenStatusService $openStatus
    ) {}

    /**
     * GET /api/v1/medical-centers
     * بدون توكن أو مع توكن مريض (للمفضلة). التقييم من متوسط تقييمات العيادات التابعة للمركز.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'governorate_id' => ['nullable', 'integer', 'min:1'],
            'gov_id' => ['nullable', 'integer', 'min:1'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_open' => ['nullable'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $governorateId = $validated['governorate_id'] ?? $validated['gov_id'] ?? null;
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);
        $limit = min(max($limit, 1), 50);

        $filterOpen = $request->has('is_open')
            ? filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $reviewStats = DB::table('reviews')
            ->join('clinics', 'reviews.clinic_id', '=', 'clinics.id')
            ->whereNotNull('clinics.medical_center_id')
            ->select('clinics.medical_center_id')
            ->selectRaw('AVG(reviews.rating) as avg_rating')
            ->selectRaw('COUNT(reviews.id) as cnt')
            ->groupBy('clinics.medical_center_id');

        $query = MedicalCenter::query()
            ->select('medical_centers.*')
            ->selectRaw('COALESCE(review_stats.avg_rating, 0) as sort_avg_rating')
            ->selectRaw('COALESCE(review_stats.cnt, 0) as reviews_count_agg')
            ->leftJoinSub($reviewStats, 'review_stats', 'review_stats.medical_center_id', '=', 'medical_centers.id')
            ->where('medical_centers.status', 'approved')
            ->with(['governorate', 'district', 'city']);

        if (!empty($validated['search'])) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $validated['search']) . '%';
            $query->where('medical_centers.name', 'like', $term);
        }

        if ($governorateId) {
            $query->where('medical_centers.governorate_id', $governorateId);
        }

        if (!empty($validated['area_id'])) {
            $areaId = (int) $validated['area_id'];
            $query->where(function ($q) use ($areaId) {
                $q->where('medical_centers.district_id', $areaId)
                    ->orWhereExists(function ($sub) use ($areaId) {
                        $sub->select(DB::raw(1))
                            ->from('clinics')
                            ->whereColumn('clinics.medical_center_id', 'medical_centers.id')
                            ->where('clinics.district_id', $areaId);
                    });
            });
        }

        if (isset($validated['rating']) && $validated['rating'] !== null && $validated['rating'] !== '') {
            $min = (float) $validated['rating'];
            $query->whereRaw('COALESCE(review_stats.avg_rating, 0) >= ?', [$min]);
        }

        $query->orderByDesc('sort_avg_rating')
            ->orderBy('medical_centers.name');

        if ($filterOpen === true) {
            $ids = (clone $query)->distinct()->pluck('medical_centers.id');
            $openIds = [];
            foreach ($ids->chunk(150) as $chunk) {
                $batch = MedicalCenter::query()
                    ->whereIn('id', $chunk)
                    ->get(['id', 'working_hours']);
                foreach ($batch as $center) {
                    if ($this->openStatus->resolveMedicalCenter($center)['is_open']) {
                        $openIds[] = $center->id;
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
            $query->whereIn('medical_centers.id', $openIds);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $patient = $this->optionalPatient();

        $favoriteSet = [];
        if ($patient && $paginator->count() > 0) {
            $favoriteSet = DB::table('patient_medical_center_favorites')
                ->where('patient_id', $patient->id)
                ->whereIn('medical_center_id', $paginator->pluck('id'))
                ->pluck('medical_center_id')
                ->flip()
                ->all();
        }

        $data = $paginator->getCollection()->map(function (MedicalCenter $center) use ($patient, $favoriteSet) {
            $avg = (float) ($center->sort_avg_rating ?? 0);
            $count = (int) ($center->reviews_count_agg ?? 0);
            $open = $this->openStatus->resolveMedicalCenter($center);

            $areaLabel = optional($center->district)->name_ar
                ?? optional($center->city)->name_ar
                ?? $center->area;

            return [
                'id' => $center->id,
                'name' => $center->name,
                'image_url' => $this->resolveImageUrl($center->logo_url),
                'bio' => $center->description,
                'governorate' => optional($center->governorate)->name_ar,
                'area' => $areaLabel,
                'average_rating' => round($avg, 2),
                'reviews_count' => $count,
                'is_favorite' => $patient ? isset($favoriteSet[$center->id]) : false,
                'is_open' => $open['is_open'],
                'status_text' => $open['status_text'],
                'location' => [
                    'lat' => $center->latitude !== null ? (float) $center->latitude : null,
                    'lng' => $center->longitude !== null ? (float) $center->longitude : null,
                ],
                'address_details' => $center->address_details ?? $center->address,
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
     * GET /api/v1/medical-centers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $center = MedicalCenter::query()
            ->where('id', $id)
            ->where('status', 'approved')
            ->with(['governorate', 'district', 'city'])
            ->first();

        if (!$center) {
            return response()->json([
                'success' => false,
                'message' => 'المركز الطبي غير موجود.',
            ], 404);
        }

        $reviewRow = DB::table('reviews')
            ->join('clinics', 'reviews.clinic_id', '=', 'clinics.id')
            ->where('clinics.medical_center_id', $center->id)
            ->selectRaw('COALESCE(AVG(reviews.rating), 0) as avg_rating')
            ->selectRaw('COUNT(reviews.id) as cnt')
            ->first();

        $avg = (float) ($reviewRow->avg_rating ?? 0);
        $reviewsCount = (int) ($reviewRow->cnt ?? 0);

        $open = $this->openStatus->resolveMedicalCenter($center);

        $patient = $this->optionalPatient();
        $isFavorite = $patient
            && DB::table('patient_medical_center_favorites')
                ->where('patient_id', $patient->id)
                ->where('medical_center_id', $center->id)
                ->exists();

        $clinics = Clinic::query()
            ->where('medical_center_id', $center->id)
            ->where('status', 'approved')
            ->with(['governorate', 'district', 'specialization'])
            ->orderBy('clinic_name')
            ->get();

        $clinicIds = $clinics->pluck('id')->all();
        $clinicAvgs = $clinicIds === []
            ? collect()
            : DB::table('reviews')
                ->whereIn('clinic_id', $clinicIds)
                ->groupBy('clinic_id')
                ->selectRaw('clinic_id, AVG(rating) as avg_rating')
                ->pluck('avg_rating', 'clinic_id');

        $specIds = $clinics->pluck('specialization_id')->filter()->unique()->values()->all();
        $centerSpecialties = $specIds === []
            ? collect()
            : Specialization::query()->whereIn('id', $specIds)->orderBy('name_ar')->get();

        $specialtiesPayload = $centerSpecialties->map(function (Specialization $s) {
            return ['id' => $s->id, 'name' => $s->name_ar];
        })->values()->all();

        $clinicsPayload = $clinics->map(function (Clinic $clinic) use ($clinicAvgs) {
            $cAvg = (float) ($clinicAvgs[$clinic->id] ?? 0);
            $specBlock = [];
            if ($clinic->specialization) {
                $specBlock[] = [
                    'id' => $clinic->specialization->id,
                    'name' => $clinic->specialization->name_ar,
                ];
            }

            return [
                'id' => $clinic->id,
                'name' => $clinic->clinic_name,
                'image_url' => $this->resolveImageUrl($clinic->main_image),
                'governorate' => optional($clinic->governorate)->name_ar,
                'area' => optional($clinic->district)->name_ar,
                'average_rating' => round($cAvg, 2),
                'specialties' => $specBlock,
            ];
        })->values()->all();

        $areaLabel = optional($center->district)->name_ar
            ?? optional($center->city)->name_ar
            ?? $center->area;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $center->id,
                'name' => $center->name,
                'image_url' => $this->resolveImageUrl($center->logo_url),
                'bio' => $center->description,
                'governorate' => optional($center->governorate)->name_ar,
                'area' => $areaLabel,
                'detailed_address' => $center->address_details ?? $center->address,
                'location' => [
                    'lat' => $center->latitude !== null ? (float) $center->latitude : null,
                    'lng' => $center->longitude !== null ? (float) $center->longitude : null,
                ],
                'is_open' => $open['is_open'],
                'status_text' => $open['status_text'],
                'average_rating' => round($avg, 2),
                'reviews_count' => $reviewsCount,
                'is_favorite' => $isFavorite,
                'contact_links' => PublicContactLinks::forMedicalCenter($center),
                'specialties' => $specialtiesPayload,
                'working_hours' => WorkingHoursApiFormatter::format($center->working_hours),
                'clinics' => $clinicsPayload,
            ],
        ]);
    }
}
