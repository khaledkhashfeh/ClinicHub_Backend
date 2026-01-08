<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class ClinicRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required fields
            'clinic_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clinics,phone',
            'email' => 'nullable|email|unique:clinics,email',
            'specialization_id' => 'required|exists:specializations,id',
            'governorate_id' => 'required|exists:governorates,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
            'address' => 'nullable|string|max:255',
            'detailed_address' => 'required|string|max:500',
            'floor' => 'nullable|integer|min:0',
            'room_number' => 'nullable|integer|min:0',
            'consultation_fee' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
            'username' => 'required|string|unique:clinics,username',
            'password' => 'required|string|min:8|confirmed',

            // Files
            'main_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'working_hours' => 'required|json',

            // Optional fields
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string|max:255',
            'services.*.price' => 'required_with:services|numeric|min:0',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB max each
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180'
        ];
    }

    public function messages(): array
    {
        return [
            'clinic_name.required' => 'اسم العيادة مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'specialization_id.required' => 'يرجى اختيار التخصص',
            'governorate_id.required' => 'يرجى اختيار المحافظة',
            'city_id.required' => 'يرجى اختيار المدينة',
            'district_id.required' => 'يرجى اختيار المنطقة',
            'detailed_address.required' => 'العنوان التفصيلي مطلوب',
            'consultation_fee.required' => 'سعر الكشفية مطلوب',
            'description.required' => 'الوصف مطلوب',
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم مستخدم من قبل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'main_image.required' => 'الصورة الرئيسية مطلوبة',
            'main_image.image' => 'يجب أن تكون الصورة الرئيسية ملف صورة',
            'working_hours.required' => 'أوقات الدوام مطلوبة',
            'working_hours.json' => 'أوقات الدوام يجب أن تكون بصيغة JSON صحيحة'
        ];
    }
}
