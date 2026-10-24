<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $practice = $this->input('mode') === 'practice';
        $full = $this->input('mode') === 'full';

        return [
            'mode' => ['required', Rule::in(['full', 'practice'])],
            'question_count' => [Rule::requiredIf($practice), 'nullable', 'integer', Rule::in([20, 50])],
            'duration_preset' => [Rule::requiredIf($full), 'nullable', Rule::in(['esl', 'standard'])],
            'practice_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:180', Rule::prohibitedIf($full)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('mode') === 'practice' && $this->filled('practice_duration_minutes') && ! is_numeric($this->input('practice_duration_minutes'))) {
                $validator->errors()->add('practice_duration_minutes', 'Enter a valid practice duration.');
            }
        }];
    }
}
