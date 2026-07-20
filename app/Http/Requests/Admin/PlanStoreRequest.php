<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\MapsPlanAttributes;

class PlanStoreRequest extends AdminFormRequest
{
    use MapsPlanAttributes;

    public function authorize(): bool
    {
        return $this->admin()?->hasPermission('plans.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:plans,slug'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_social_accounts' => ['nullable', 'integer', 'min:1'],
            'max_posts_per_month' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ], $this->featureValidationRules());
    }
}
