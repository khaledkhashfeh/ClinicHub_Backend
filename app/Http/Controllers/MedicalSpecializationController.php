<?php

namespace App\Http\Controllers;

use App\Models\MedicalSpecialization;
use App\Services\ImageKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicalSpecializationController extends Controller
{
    protected ImageKitService $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    /**
     * List all medical specializations.
     */
    public function index(): JsonResponse
    {
        $specializations = MedicalSpecialization::query()
            ->select('id', 'name', 'image_url', 'is_active')
            ->orderBy('id')
            ->get();

        return response()->json($specializations);
    }

    /**
     * Create a new medical specialization.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:medical_specializations,name'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم التخصص مطلوب.',
            'name.unique' => 'هذا التخصص موجود مسبقاً.',
        ]);

        $imageUrl = null;
        $imageFileId = null;
        if ($request->hasFile('image')) {
            $uploadResult = $this->imageKitService->upload($request->file('image'), 'medical-specializations');
            $imageUrl = $uploadResult['url'];
            $imageFileId = $uploadResult['fileId'];
        }

        $specialization = MedicalSpecialization::create([
            'name' => $data['name'],
            'image_url' => $imageUrl,
            'image_file_id' => $imageFileId,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التخصص الطبي بنجاح.',
            'data' => $this->formatResource($specialization),
        ], 201);
    }

    /**
     * Update an existing medical specialization.
     */
    public function update(Request $request, MedicalSpecialization $medicalSpecialization): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('medical_specializations', 'name')->ignore($medicalSpecialization->id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم التخصص مطلوب.',
            'name.unique' => 'هذا التخصص موجود مسبقاً.',
        ]);

        if (array_key_exists('name', $data)) {
            $medicalSpecialization->name = $data['name'];
        }

        if (array_key_exists('is_active', $data)) {
            $medicalSpecialization->is_active = (bool) $data['is_active'];
        }

        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إن وجدت
            if ($medicalSpecialization->image_file_id) {
                $this->imageKitService->delete($medicalSpecialization->image_file_id);
            }
            
            $uploadResult = $this->imageKitService->upload($request->file('image'), 'medical-specializations');
            $medicalSpecialization->image_url = $uploadResult['url'];
            $medicalSpecialization->image_file_id = $uploadResult['fileId'];
        }

        $medicalSpecialization->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التخصص بنجاح.',
            'data' => $this->formatResource($medicalSpecialization),
        ]);
    }

    /**
     * Delete a medical specialization.
     */
    public function destroy(MedicalSpecialization $medicalSpecialization): JsonResponse
    {
        // حذف الصورة من ImageKit
        if ($medicalSpecialization->image_file_id) {
            $this->imageKitService->delete($medicalSpecialization->image_file_id);
        }
        
        $deletedName = $medicalSpecialization->name;
        $medicalSpecialization->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف تخصص {$deletedName} بنجاح.",
        ]);
    }

    private function formatResource(MedicalSpecialization $specialization): array
    {
        return [
            'id' => $specialization->id,
            'name' => $specialization->name,
            'image_url' => $specialization->image_url,
            'is_active' => (bool) $specialization->is_active,
        ];
    }

}

