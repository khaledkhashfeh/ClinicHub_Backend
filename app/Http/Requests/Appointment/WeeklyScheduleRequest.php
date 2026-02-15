<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class WeeklyScheduleRequest extends FormRequest
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
            'appointment_duration' => 'required|integer|min:15|max:120',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day_of_week' => 'required|integer|between:1,7',
            'weekly_schedule.*.start_time' => 'required|date_format:H:i',
            'weekly_schedule.*.end_time' => 'required|date_format:H:i',
            'weekly_schedule.*.breaks' => 'nullable|array',
            'weekly_schedule.*.breaks.*.start' => 'required_with:weekly_schedule.*.breaks|date_format:H:i',
            'weekly_schedule.*.breaks.*.end' => 'required_with:weekly_schedule.*.breaks|date_format:H:i',
        ];
    }
}
