<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class CreateManualSlotsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization logic
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
            'date' => 'required|date|after_or_equal:today',
            'slots' => 'required|array|min:1',
            'slots.*.start' => 'required|date_format:H:i',
            'slots.*.end' => 'required|date_format:H:i|after:slots.*.start',
            'replace_existing' => 'nullable|boolean',
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
            'date.required' => 'Date is required',
            'date.date' => 'Date must be a valid date',
            'date.after_or_equal' => 'Date must be today or later',
            'slots.required' => 'At least one slot is required',
            'slots.min' => 'At least one slot is required',
            'slots.*.start.required' => 'Each slot must have a start time',
            'slots.*.start.date_format' => 'Slot start time must be in HH:mm format',
            'slots.*.end.required' => 'Each slot must have an end time',
            'slots.*.end.date_format' => 'Slot end time must be in HH:mm format',
            'slots.*.end.after' => 'Slot end time must be after start time',
        ];
    }
}
