<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'subtitle', 'price_monthly', 'price_yearly',
        'max_brands', 'max_social_accounts', 'max_posts_per_month',
        'bulk_scheduling', 'ai_insights', 'white_label_reports', 'api_access',
        'feature_list',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'bulk_scheduling' => 'boolean',
            'ai_insights' => 'boolean',
            'white_label_reports' => 'boolean',
            'api_access' => 'boolean',
            'feature_list' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return list<array{name: string, enabled: bool}> */
    public function editableFeatures(): array
    {
        $list = $this->feature_list;

        if (is_array($list) && $list !== []) {
            return array_values(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    $name = trim($item);

                    return $name === '' ? null : ['name' => $name, 'enabled' => true];
                }

                if (! is_array($item)) {
                    return null;
                }

                $name = trim((string) ($item['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'enabled' => (bool) ($item['enabled'] ?? true),
                ];
            }, $list)));
        }

        $fallback = [];

        if ($this->bulk_scheduling) {
            $fallback[] = ['name' => 'Bulk Scheduling', 'enabled' => true];
        }
        if ($this->ai_insights) {
            $fallback[] = ['name' => 'AI Insights', 'enabled' => true];
        }
        if ($this->white_label_reports) {
            $fallback[] = ['name' => 'White Label Reports', 'enabled' => true];
        }
        if ($this->api_access) {
            $fallback[] = ['name' => 'API Access', 'enabled' => true];
        }

        return $fallback;
    }

    /** @return list<string> */
    public function enabledFeatureNames(): array
    {
        return collect($this->editableFeatures())
            ->filter(fn (array $feature) => $feature['enabled'])
            ->map(fn (array $feature) => $feature['name'])
            ->values()
            ->all();
    }

    public function isUnlimited(string $field): bool
    {
        return $this->{$field} === null;
    }

    public function formatLimit(?int $value): string
    {
        return $value === null ? 'Unlimited' : (string) $value;
    }
}
