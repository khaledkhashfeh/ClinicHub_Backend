<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class DoctorLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string', // phone or email
            'password' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'يرجى إدخال رقم الهاتف أو البريد الإلكتروني',
            'password.required' => 'يرجى إدخال كلمة المرور'
        ];
    }
}
