<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clinic = auth()->guard('clinic')->user();
        $clinicId = $clinic ? $clinic->id : null;

        return [
            // Optional fields (can be updated partially)
            'clinic_name' => 'sometimes|nullable|string|max:255',
            'phone' => "sometimes|nullable|string|unique:clinics,phone,{$clinicId},id",
            'specialization_id' => 'sometimes|nullable|exists:specializations,id',
            'governorate_id' => 'sometimes|nullable|exists:governorates,id',
            'city_id' => 'sometimes|nullable|exists:cities,id',
            'district_id' => 'sometimes|nullable|exists:districts,id',
            'detailed_address' => 'sometimes|nullable|string|max:500',
            'consultation_fee' => 'sometimes|nullable|numeric|min:0',
            'description' => 'sometimes|nullable|string|max:1000',
            'username' => "sometimes|nullable|string|unique:clinics,username,{$clinicId},id",
            'password' => 'sometimes|nullable|string|min:8|confirmed',

            // Files (optional)
            'main_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'working_hours' => 'sometimes|nullable|json',

            // Optional fields
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string|max:255',
            'services.*.price' => 'required_with:services|numeric|min:0',
            'gallery_images' => 'sometimes|nullable|array',
            'gallery_images.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:5120', // 5MB max each
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180'
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'username.unique' => 'اسم المستخدم مستخدم من قبل',
            'main_image.image' => 'يجب أن تكون الصورة ملف صورة',
            'working_hours.json' => 'أوقات الدوام يجب أن تكون بصيغة JSON صحيحة'
        ];
    }
}