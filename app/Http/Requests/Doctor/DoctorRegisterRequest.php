<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class DoctorRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required fields
            'username' => 'required|string|max:255|unique:doctors,username',
            'license_number' => 'required|string|max:255|unique:doctors,license_number',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'governorate_id' => 'required|exists:governorates,id',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'specializations_ids' => 'required|array|min:1',
            'specializations_ids.*' => 'exists:specializations,id',
            'practicing_profession_date' => 'required|integer|min:1950|max:' . date('Y'),
            'bio' => 'required|string|max:2000',
            'password' => 'required|string|min:8|confirmed',

            // Optional fields
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'distinguished_specialties' => 'nullable|string|max:1000',
            'certifications' => 'nullable|array',
            'certifications.*.name' => 'required_with:certifications|string|max:255',
            'certifications.*.image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:10240',
            'facebook_link' => 'nullable|url|max:500',
            'instagram_link' => 'nullable|url|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم مستخدم من قبل',
            'license_number.required' => 'رقم الترخيص مطلوب',
            'license_number.unique' => 'رقم الترخيص مستخدم من قبل',
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'governorate_id.required' => 'يرجى اختيار المحافظة',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'يرجى تحديد الجنس',
            'specializations_ids.required' => 'يرجى اختيار التخصص على الأقل',
            'practicing_profession_date.required' => 'تاريخ مزاولة المهنة مطلوب',
            'bio.required' => 'النبذة التعريفية مطلوبة',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة'
        ];
    }
}
