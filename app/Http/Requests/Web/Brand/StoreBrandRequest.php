<?php

namespace App\Http\Requests\Web\Brand;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:500'],
            'industry' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:50'],
            'tone' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Brand name is required.',
            'industry.required' => 'Please select an industry.',
            'country.required' => 'Please select a country.',
            'website.url' => 'Please enter a valid website URL.',
            'logo.image' => 'Logo must be an image file.',
            'logo.max' => 'Logo must not be larger than 2 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $name = $this->input('name');

            if (! $name || ! $this->user()) {
                return;
            }

            $slug = Str::slug($name);

            $exists = Brand::query()
                ->where('user_id', $this->user()->id)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'name',
                    'This brand name already exists. Please use a different name.'
                );
            }
        });
    }
}
