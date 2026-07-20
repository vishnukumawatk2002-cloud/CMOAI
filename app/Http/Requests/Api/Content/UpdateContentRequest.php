<?php

namespace App\Http\Requests\Api\Content;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,approved,scheduled,published,failed'],
            'platform' => ['nullable', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
