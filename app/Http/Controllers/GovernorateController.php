<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GovernorateController extends Controller
{
    /**
     * Get all governorates.
     */
    public function index(): JsonResponse
    {
        $governorates = Governorate::query()
            ->select('id', 'name_ar as name')
            ->orderBy('id')
            ->get();

        return response()->json($governorates);
    }

    /**
     * Get districts (cities) by governorate.
     */
    public function districts(Governorate $governorate): JsonResponse
    {
        $districts = $governorate->cities()
            ->select('id', 'governorate_id', 'name_ar as name')
            ->orderBy('id')
            ->get();

        return response()->json($districts);
    }

    /**
     * Add a new district (city) to a governorate.
     */
    public function storeDistrict(Request $request, Governorate $governorate): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('cities', 'name_ar')->where(
                        fn ($query) => $query->where('governorate_id', $governorate->id)
                    ),
                ],
            ],
            [
                'name.required' => 'اسم المنطقة مطلوب.',
                'name.unique' => 'هذه المنطقة مضافة مسبقاً.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $city = City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => $validated['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنطقة بنجاح.',
            'data' => [
                'id' => $city->id,
                'governorate_id' => $city->governorate_id,
                'name' => $city->name_ar,
            ],
        ], 201);
    }
}

