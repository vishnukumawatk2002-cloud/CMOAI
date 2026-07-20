<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'name',
        'slug',
        'website',
        'industry',
        'country',
        'language',
        'tone',
        'founded_year',
        'short_description',
        'logo_path',
        'setup_step',
        'setup_completed_at',
        'sources_updated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'setup_completed_at' => 'datetime',
            'sources_updated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function voiceSettings()
    {
        return $this->hasOne(BrandVoiceSetting::class);
    }

    public function knowledgeBase()
    {
        return $this->hasOne(BrandKnowledgeBase::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(BrandAsset::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function suggestedPrompts(): HasMany
    {
        return $this->hasMany(AiSuggestedPrompt::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    public function isSetupComplete(): bool
    {
        return $this->setup_completed_at !== null;
    }
}
