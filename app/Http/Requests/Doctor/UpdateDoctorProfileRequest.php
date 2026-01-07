<?php

namespace App\Http\Requests\Doctor;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $doctorId = $this->user()->doctor->id;
        $currentDoctor = $this->user()->doctor;

        // Check if the authenticated user is the doctor whose profile is being updated
        return $currentDoctor && $currentDoctor->id == $doctorId;
    }

    public function rules(): array
    {
        $userId = $this->user()->doctor->user_id;

        return [
            'username' => "sometimes|string|max:255|unique:doctors,username,{$this->user()->doctor->id},id",
            'license_number' => "sometimes|string|max:255|unique:doctors,license_number,{$this->user()->doctor->id},id",
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => "sometimes|string|unique:users,phone,{$userId},id",
            'email' => "sometimes|email|unique:users,email,{$userId},id",
            'governorate_id' => 'sometimes|exists:governorates,id',
            'district_id' => 'sometimes|exists:districts,id',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female',
            'specializations_ids' => 'sometimes|array',
            'specializations_ids.*' => 'exists:specializations,id',
            'practicing_profession_date' => 'sometimes|integer|min:1950|max:' . date('Y'),
            'bio' => 'sometimes|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'distinguished_specialties' => 'nullable|string|max:1000',
            'certifications' => 'nullable|array',
            'certifications.*.name' => 'required_with:certifications|string|max:255',
            'certifications.*.image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:10240',
            'facebook_link' => 'nullable|url|max:500',
            'instagram_link' => 'nullable|url|max:500',
            'consultation_price' => 'sometimes|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.max' => 'حجم الصورة يجب أن يكون أقل من 5 ميجابايت'
        ];
    }
}
