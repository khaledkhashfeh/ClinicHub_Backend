<?php

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\MedicalTerm;
use App\Models\Prescription;
use App\Models\VisitDiagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalSuggestionsController extends Controller
{
    private const LIMIT = 10;

    /**
     * GET /api/v1/medical-info/suggestions
     * Query: field (chronic_disease | medication | surgery), query (search text)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:chronic_disease,medication,surgery'],
            'query' => ['required', 'string', 'min:1', 'max:100'],
        ], [
            'field.required' => 'حقل نوع الاقتراح مطلوب (chronic_disease, medication, surgery).',
            'field.in' => 'نوع الاقتراح غير صالح.',
            'query.required' => 'نص البحث مطلوب.',
        ]);

        $field = $validated['field'];
        $q = trim($validated['query']);
        $like = '%' . $q . '%';

        $items = $this->searchSuggestions($field, $like);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    private function searchSuggestions(string $field, string $like): array
    {
        $limitStandard = 6;
        $limitHistory = 4;

        // 1) المصطلحات القياسية من جدول medical_terms
        $standard = MedicalTerm::query()
            ->where('category', $field)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('name_en', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limitStandard)
            ->get(['id', 'name', 'category'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'category' => $row->category,
            ])
            ->all();

        $collected = collect($standard);
        $existingNames = $collected->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();

        // 2) تاريخ الإدخال: مصطلحات مستخدمة سابقاً (مميزة، غير مكررة، محدودة)
        $history = $this->getHistorySuggestions($field, $like, $limitHistory, $existingNames);

        foreach ($history as $item) {
            $collected->push($item);
        }

        return $collected->take(self::LIMIT)->values()->all();
    }

    private function getHistorySuggestions(string $field, string $like, int $limit, $existingNames): array
    {
        $out = [];

        if ($field === MedicalTerm::CATEGORY_CHRONIC_DISEASE) {
            $names = VisitDiagnosis::query()
                ->select('condition_name as name')
                ->whereNotNull('condition_name')
                ->where('condition_name', '!=', '')
                ->where('condition_name', 'like', $like)
                ->distinct()
                ->limit($limit * 2)
                ->pluck('name');
        } elseif ($field === MedicalTerm::CATEGORY_MEDICATION) {
            $names = Prescription::query()
                ->select('medication_name as name')
                ->whereNotNull('medication_name')
                ->where('medication_name', '!=', '')
                ->where('medication_name', 'like', $like)
                ->distinct()
                ->limit($limit * 2)
                ->pluck('name');
        } elseif ($field === MedicalTerm::CATEGORY_SURGERY) {
            // يمكن لاحقاً ربطه بجدول عمليات أو تصنيف معين في visit_diagnoses
            $names = collect();
        } else {
            return [];
        }

        $count = 0;
        foreach ($names as $name) {
            if ($count >= $limit) {
                break;
            }
            $key = mb_strtolower($name);
            if ($existingNames->has($key)) {
                continue;
            }
            $existingNames[$key] = true;
            $out[] = [
                'id' => null,
                'name' => $name,
                'category' => $field,
            ];
            $count++;
        }

        return $out;
    }
}
