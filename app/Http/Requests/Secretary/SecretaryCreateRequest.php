<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class SecretaryCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'     => ['required', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255'],
            'phone_number'  => ['required', 'regex:/^(0|\+963)\d{9}$/'],
            'email'         => ['nullable', 'email'],
            'username'      => ['required', 'string', 'max:255'],
            'password'      => ['required', 'string', 'min:8', 'max:18', 'confirmed'],
            'date_of_birth' => ['required', 'date'],
            'profile_image' => ['nullable', 'string'],
            'gender'        => ['required', 'in:male,female'],
            'entity_id'     => ['required', 'integer'],
            'entity_type'   => ['required', 'in:clinic,medical_center'],
        ];
    }
}
