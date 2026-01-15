<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class ClinicLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifyer' => 'required|string', // username or phone - using the API spec spelling
            'password' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'identifyer.required' => 'يرجى إدخال اسم المستخدم أو رقم الهاتف',
            'password.required' => 'يرجى إدخال كلمة المرور'
        ];
    }
}
