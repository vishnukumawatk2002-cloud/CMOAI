<?php

namespace App\Http\Requests\Api\Content;

use Illuminate\Foundation\Http\FormRequest;

class GenerateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type' => ['required', 'string', 'max:50'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'max:50'],
            'prompt' => ['required', 'string', 'max:5000'],
        ];
    }
}
