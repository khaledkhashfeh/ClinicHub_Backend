<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class DoctorWorkSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization logic
        // Example: Check if user is the doctor or clinic owner
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
            'clinic_id' => 'required|exists:clinics,id',
            'doctor_id' => 'required|exists:doctors,id',
            'method_id' => 'required|exists:methods,id',
            'appointment_period' => 'required|integer|min:15|max:120',
            'queue' => 'required|boolean',
            'queue_number' => 'required_if:queue,true|nullable|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'clinic_id.required' => 'Clinic ID is required',
            'clinic_id.exists' => 'The specified clinic does not exist',
            'doctor_id.required' => 'Doctor ID is required',
            'doctor_id.exists' => 'The specified doctor does not exist',
            'method_id.required' => 'Method ID is required',
            'method_id.exists' => 'The specified method does not exist',
            'appointment_period.required' => 'Appointment period is required',
            'appointment_period.integer' => 'Appointment period must be a number',
            'appointment_period.min' => 'Appointment period must be at least 15 minutes',
            'appointment_period.max' => 'Appointment period must not exceed 120 minutes',
            'queue.required' => 'Queue setting is required',
            'queue.boolean' => 'Queue must be true or false',
            'queue_number.required_if' => 'Queue number is required when queue is enabled',
            'queue_number.integer' => 'Queue number must be a number',
            'queue_number.min' => 'Queue number must be at least 1',
        ];
    }
}
