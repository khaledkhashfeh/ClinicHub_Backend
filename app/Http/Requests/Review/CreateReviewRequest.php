<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by auth middleware and controller (patient check)
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => ['required', 'integer', 'min:1'],
            'target_type' => ['required', 'string', 'in:doctor,clinic,center'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.required' => 'معرف الهدف (طبيب/عيادة/مركز) مطلوب.',
            'target_id.integer' => 'معرف الهدف يجب أن يكون رقماً صحيحاً.',
            'target_id.min' => 'معرف الهدف غير صالح.',
            'target_type.required' => 'نوع الهدف مطلوب (doctor, clinic, center).',
            'target_type.in' => 'نوع الهدف يجب أن يكون: doctor أو clinic أو center.',
            'rating.min' => 'التقييم يجب أن يكون بين 1 و 5.',
            'rating.max' => 'التقييم يجب أن يكون بين 1 و 5.',
            'comment.max' => 'طول التعليق يجب ألا يتجاوز 2000 حرف.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            $hasRating = isset($data['rating']) && $data['rating'] !== null && $data['rating'] !== '';
            $hasComment = isset($data['comment']) && $data['comment'] !== null && $data['comment'] !== '';

            if (!$hasRating && !$hasComment) {
                $validator->errors()->add('rating', 'At least one of rating or comment must be provided.');
                $validator->errors()->add('comment', 'At least one of rating or comment must be provided.');
            }
        });
    }
}

