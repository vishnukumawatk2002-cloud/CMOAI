<?php

namespace App\Http\Requests\Admin\Concerns;

trait MapsPlanAttributes
{
    public function planAttributes(): array
    {
        $features = $this->normalizedFeatures();
        $enabledNames = collect($features)
            ->filter(fn (array $feature) => $feature['enabled'])
            ->map(fn (array $feature) => strtolower($feature['name']))
            ->all();

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->filled('subtitle') ? trim((string) $this->subtitle) : null,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'max_brands' => null,
            'max_social_accounts' => $this->filled('max_social_accounts') ? $this->max_social_accounts : null,
            'max_posts_per_month' => $this->filled('max_posts_per_month') ? $this->max_posts_per_month : null,
            'feature_list' => $features,
            'bulk_scheduling' => in_array('bulk scheduling', $enabledNames, true),
            'ai_insights' => in_array('ai insights', $enabledNames, true),
            'white_label_reports' => in_array('white label reports', $enabledNames, true),
            'api_access' => in_array('api access', $enabledNames, true),
            'is_active' => $this->has('is_active'),
            'sort_order' => $this->sort_order,
        ];
    }

    /** @return list<array{name: string, enabled: bool}> */
    private function normalizedFeatures(): array
    {
        $raw = $this->input('features', []);

        if (! is_array($raw)) {
            return [];
        }

        $features = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $features[] = [
                'name' => mb_substr($name, 0, 120),
                'enabled' => ! empty($item['enabled']),
            ];
        }

        return array_values($features);
    }

    /** @return array<string, mixed> */
    protected function featureValidationRules(): array
    {
        return [
            'features' => ['nullable', 'array'],
            'features.*.name' => ['nullable', 'string', 'max:120'],
            'features.*.enabled' => ['nullable'],
        ];
    }
}
