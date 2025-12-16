<?php

namespace App\Http\Controllers;

use App\Models\MedicalSpecialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MedicalSpecializationController extends Controller
{
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
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('medical_specializations', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        $specialization = MedicalSpecialization::create([
            'name' => $data['name'],
            'image_url' => $imageUrl,
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
            if ($medicalSpecialization->image_url) {
                $this->deleteOldImage($medicalSpecialization->image_url);
            }
            $path = $request->file('image')->store('medical_specializations', 'public');
            $medicalSpecialization->image_url = Storage::disk('public')->url($path);
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
        if ($medicalSpecialization->image_url) {
            $this->deleteOldImage($medicalSpecialization->image_url);
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

    private function deleteOldImage(string $url): void
    {
        // Convert url back to storage path if it belongs to public disk
        $publicUrl = Storage::disk('public')->url('');
        if (str_starts_with($url, $publicUrl)) {
            $relative = ltrim(str_replace($publicUrl, '', $url), '/');
            Storage::disk('public')->delete($relative);
        }
    }
}

