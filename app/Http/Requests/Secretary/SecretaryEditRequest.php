<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecretaryEditRequest extends FormRequest
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
            'phone_number'  => ['required', 'regex:/^(0|\+963)\d{9}$/'],
            'first_name'     => ['sometimes', 'string', 'max:255'],
            'last_name'      => ['sometimes', 'string', 'max:255'],
            'new_phone_number'  => ['sometimes', 'regex:/^(0|\+963)\d{9}$/'],
            'email'         => ['sometimes', 'email'],
            'username'      => ['sometimes', 'string', 'max:255'],
            'entity_type'   => ['sometimes', 'in:clinic,medical_center'],
            'entity_id'     => ['sometimes', 'integer'],
        ];
    }
}
