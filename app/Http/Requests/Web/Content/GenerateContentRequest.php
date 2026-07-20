<?php

namespace App\Http\Requests\Web\Content;

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
            'content_type' => ['required', 'in:post,carousel,reel_script,image_caption,hashtags,thirty_day_plan,thread'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['in:facebook,instagram,linkedin,x,youtube'],
            'prompt' => ['required', 'string', 'max:5000'],
        ];
    }
}
