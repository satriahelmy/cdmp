<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveExamAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected_option_source_index' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_flagged' => ['sometimes', 'boolean'],
        ];
    }
}
