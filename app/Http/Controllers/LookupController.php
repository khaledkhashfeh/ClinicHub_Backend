<?php

namespace App\Http\Controllers;

use App\Models\DiagnosisCode;
use App\Models\LabRequest;
use App\Models\LabTestCatalog;
use App\Models\MedicalTerm;
use App\Models\Prescription;
use App\Models\VisitDiagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    private const LIMIT = 10;

    /**
     * GET /api/v1/lookup/diagnosis-codes
     * البحث بأكواد التشخيص (ICD-10) أو باسم التشخيص.
     */
    public function diagnosisCodes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:100'],
        ], [
            'query.required' => 'نص البحث مطلوب.',
        ]);

        $q = trim($validated['query']);
        $like = '%' . $q . '%';

        $items = DiagnosisCode::query()
            ->where(function ($query) use ($like, $q) {
                $query->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('name_en', 'like', $like);
            })
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get(['code', 'name'])
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * GET /api/v1/lookup/names
     * type: diagnosis | medication | lab_test
     * اقتراحات الأسماء فقط (بدون أكواد) للتشخيص، الأدوية، التحاليل.
     */
    public function names(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:diagnosis,medication,lab_test'],
            'query' => ['required', 'string', 'min:1', 'max:100'],
        ], [
            'type.required' => 'نوع البحث مطلوب (diagnosis, medication, lab_test).',
            'type.in' => 'نوع البحث غير صالح.',
            'query.required' => 'نص البحث مطلوب.',
        ]);

        $type = $validated['type'];
        $q = trim($validated['query']);
        $like = '%' . $q . '%';

        $items = $this->searchNamesByType($type, $like);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    private function searchNamesByType(string $type, string $like): array
    {
        $limitStandard = 6;
        $limitHistory = 4;

        if ($type === 'diagnosis') {
            $category = MedicalTerm::CATEGORY_CHRONIC_DISEASE;
            $standard = MedicalTerm::query()
                ->where('category', $category)
                ->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('name_en', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitStandard)
                ->get(['id', 'name']);
            $historyNames = VisitDiagnosis::query()
                ->select('condition_name as name')
                ->whereNotNull('condition_name')
                ->where('condition_name', '!=', '')
                ->where('condition_name', 'like', $like)
                ->distinct()
                ->limit($limitHistory * 2)
                ->pluck('name');
        } elseif ($type === 'medication') {
            $category = MedicalTerm::CATEGORY_MEDICATION;
            $standard = MedicalTerm::query()
                ->where('category', $category)
                ->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('name_en', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitStandard)
                ->get(['id', 'name']);
            $historyNames = Prescription::query()
                ->select('medication_name as name')
                ->whereNotNull('medication_name')
                ->where('medication_name', '!=', '')
                ->where('medication_name', 'like', $like)
                ->distinct()
                ->limit($limitHistory * 2)
                ->pluck('name');
        } elseif ($type === 'lab_test') {
            $standard = LabTestCatalog::query()
                ->where('name', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orderBy('name')
                ->limit($limitStandard)
                ->get(['id', 'name']);
            $historyNames = LabRequest::query()
                ->select('test_name as name')
                ->whereNotNull('test_name')
                ->where('test_name', '!=', '')
                ->where('test_name', 'like', $like)
                ->distinct()
                ->limit($limitHistory * 2)
                ->pluck('name');
        } else {
            return [];
        }

        $existing = collect($standard)->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $result = $standard->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->all();

        foreach ($historyNames as $name) {
            if (count($result) >= self::LIMIT) {
                break;
            }
            $key = mb_strtolower($name);
            if ($existing->has($key)) {
                continue;
            }
            $existing[$key] = true;
            $result[] = ['id' => null, 'name' => $name];
        }

        return array_slice($result, 0, self::LIMIT);
    }
}
