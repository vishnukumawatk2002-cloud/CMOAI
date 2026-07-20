<?php

namespace App\Http\Requests\Api\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tone_style' => ['nullable', 'string', 'max:100'],
            'company_description' => ['nullable', 'string', 'max:2000'],
            'products_services' => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:50'],
            'avoid_words' => ['nullable', 'array'],
            'avoid_words.*' => ['string', 'max:50'],
        ];
    }
}
