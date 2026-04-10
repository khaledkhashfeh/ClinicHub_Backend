<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOptionalPatient;
use App\Http\Controllers\Concerns\ResolvesPublicMediaUrls;
use App\Models\Doctor;
use App\Support\ArabicSearchNormalizer;
use App\Support\PublicContactLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicDoctorController extends Controller
{
    use ResolvesOptionalPatient;
    use ResolvesPublicMediaUrls;

    /**
     * GET /api/v1/doctors
     * بدون توكن أو مع توكن مريض (للمفضلة).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'governorate_id' => ['nullable', 'integer', 'min:1'],
            'gov_id' => ['nullable', 'integer', 'min:1'],
            'area_id' => ['nullable', 'integer', 'min:1'],
            'specialty_id' => ['nullable', 'integer', 'min:1'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort_by' => ['nullable', 'string', 'in:top_rated'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $governorateId = $validated['governorate_id'] ?? $validated['gov_id'] ?? null;
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);
        $limit = min(max($limit, 1), 50);

        $minRating = $validated['min_rating'] ?? $validated['rating'] ?? null;

        $reviewStats = DB::table('reviews')
            ->select('doctor_id')
            ->selectRaw('AVG(rating) as avg_rating')
            ->selectRaw('COUNT(*) as cnt')
            ->whereNotNull('doctor_id')
            ->groupBy('doctor_id');

        $query = Doctor::query()
            ->select('doctors.*')
            ->selectRaw('COALESCE(review_stats.avg_rating, 0) as sort_avg_rating')
            ->selectRaw('COALESCE(review_stats.cnt, 0) as reviews_count_agg')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->leftJoinSub($reviewStats, 'review_stats', 'review_stats.doctor_id', '=', 'doctors.id')
            ->where('doctors.status', 'approved')
            ->where('users.status', 'approved')
            ->with(['governorate', 'district', 'specializations', 'user']);

        if (!empty($validated['search'])) {
            $norm = ArabicSearchNormalizer::normalize($validated['search']);
            $pattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $norm) . '%';
            $fn = ArabicSearchNormalizer::sqlNormalizeColumn('users.first_name');
            $ln = ArabicSearchNormalizer::sqlNormalizeColumn('users.last_name');
            $query->whereRaw(
                "CONCAT({$fn}, ' ', {$ln}) LIKE ?",
                [$pattern]
            );
        }

        if ($governorateId) {
            $query->where('doctors.governorate_id', $governorateId);
        }

        if (!empty($validated['area_id'])) {
            $query->where('doctors.district_id', $validated['area_id']);
        }

        if (!empty($validated['specialty_id'])) {
            $sid = (int) $validated['specialty_id'];
            $query->whereExists(function ($q) use ($sid) {
                $q->select(DB::raw(1))
                    ->from('doctor_specialization')
                    ->whereColumn('doctor_specialization.doctor_id', 'doctors.id')
                    ->where('doctor_specialization.specialization_id', $sid);
            });
        }

        if (!empty($validated['gender'])) {
            $query->where('users.gender', $validated['gender']);
        }

        if ($minRating !== null && $minRating !== '') {
            $query->whereRaw('COALESCE(review_stats.avg_rating, 0) >= ?', [(float) $minRating]);
        }

        $sortBy = $validated['sort_by'] ?? 'top_rated';
        if ($sortBy === 'top_rated' || $sortBy === null) {
            $query->orderByDesc('sort_avg_rating')
                ->orderBy('users.first_name')
                ->orderBy('users.last_name');
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $patient = $this->optionalPatient();

        $favoriteSet = [];
        if ($patient && $paginator->count() > 0) {
            $favoriteSet = DB::table('patient_doctor_favorites')
                ->where('patient_id', $patient->id)
                ->whereIn('doctor_id', $paginator->pluck('id'))
                ->pluck('doctor_id')
                ->flip()
                ->all();
        }

        $data = $paginator->getCollection()->map(function (Doctor $doctor) use ($patient, $favoriteSet) {
            $user = $doctor->user;
            $avg = (float) ($doctor->sort_avg_rating ?? 0);
            $spec = $doctor->specializations->first();

            return [
                'id' => $doctor->id,
                'full_name' => $user
                    ? 'د. ' . trim($user->first_name . ' ' . $user->last_name)
                    : 'د.',
                'image_url' => $this->resolveImageUrl($doctor->image),
                'governorate' => optional($doctor->governorate)->name_ar,
                'area' => optional($doctor->district)->name_ar,
                'specialty' => $spec ? $spec->name_ar : ($doctor->specialty ?? null),
                'gender' => $user->gender ?? null,
                'average_rating' => round($avg, 2),
                'is_favorite' => $patient ? isset($favoriteSet[$doctor->id]) : false,
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
     * GET /api/v1/doctors/{id}
     */
    public function show(int $id): JsonResponse
    {
        $doctor = Doctor::query()
            ->where('doctors.id', $id)
            ->where('doctors.status', 'approved')
            ->whereHas('user', function ($q) {
                $q->where('status', 'approved');
            })
            ->with([
                'user',
                'governorate',
                'district',
                'specializations',
                'certifications' => function ($q) {
                    $q->orderBy('id');
                },
                'clinics' => function ($q) {
                    $q->where('clinics.status', 'approved')
                        ->orderBy('clinics.clinic_name')
                        ->with(['governorate', 'district', 'specialization']);
                },
            ])
            ->first();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'الطبيب غير موجود.',
            ], 404);
        }

        $user = $doctor->user;

        $reviewRow = DB::table('reviews')
            ->where('doctor_id', $doctor->id)
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating')
            ->selectRaw('COUNT(*) as cnt')
            ->first();

        $avg = (float) ($reviewRow->avg_rating ?? 0);
        $reviewsCount = (int) ($reviewRow->cnt ?? 0);

        $clinicIds = $doctor->clinics->pluck('id')->all();
        $clinicAvgs = $clinicIds === []
            ? collect()
            : DB::table('reviews')
                ->whereIn('clinic_id', $clinicIds)
                ->groupBy('clinic_id')
                ->selectRaw('clinic_id, AVG(rating) as avg_rating')
                ->pluck('avg_rating', 'clinic_id');

        $yearsExperience = null;
        if ($doctor->practicing_profession_date) {
            $startYear = (int) $doctor->practicing_profession_date;
            $cy = (int) date('Y');
            if ($startYear > 1900 && $startYear <= $cy) {
                $yearsExperience = max(0, $cy - $startYear);
            }
        }

        $patient = $this->optionalPatient();
        $isFavorite = $patient
            && DB::table('patient_doctor_favorites')
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->exists();

        $clinicsPayload = $doctor->clinics->map(function ($clinic) use ($clinicAvgs) {
            $cAvg = (float) ($clinicAvgs[$clinic->id] ?? 0);

            return [
                'id' => $clinic->id,
                'name' => $clinic->clinic_name,
                'image_url' => $this->resolveImageUrl($clinic->main_image),
                'governorate' => optional($clinic->governorate)->name_ar,
                'area' => optional($clinic->district)->name_ar,
                'average_rating' => round($cAvg, 2),
            ];
        })->values()->all();

        $certificates = $doctor->certifications->map(function ($cert) {
            return [
                'id' => $cert->id,
                'title' => $cert->name,
                'image_url' => $this->resolveImageUrl($cert->image_url),
                'year' => $cert->year,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'full_name' => $user
                    ? 'د. ' . trim($user->first_name . ' ' . $user->last_name)
                    : 'د.',
                'image_url' => $this->resolveImageUrl($doctor->image),
                'specialties' => $doctor->specializations->map(function ($s) {
                    return ['id' => $s->id, 'name' => $s->name_ar];
                })->values()->all(),
                'sub_specialty' => $doctor->distinguished_specialties,
                'governorate' => optional($doctor->governorate)->name_ar,
                'area' => optional($doctor->district)->name_ar,
                'bio' => $doctor->bio,
                'average_rating' => round($avg, 2),
                'reviews_count' => $reviewsCount,
                'years_of_experience' => $yearsExperience,
                'is_favorite' => $isFavorite,
                'contact_links' => $user ? PublicContactLinks::forDoctor($user, $doctor) : [],
                'clinics' => $clinicsPayload,
                'certificates' => $certificates,
            ],
        ]);
    }
}
