<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'folder_id',
        'parent_id',
        'ai_generation_request_id',
        'content_type',
        'platform',
        'title',
        'body',
        'status',
        'variation_number',
        'generation_prompt',
        'scheduled_at',
        'published_at',
        'external_post_id',
        'external_post_url',
        'reach',
        'engagement_rate',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'engagement_rate' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ContentFolder::class, 'folder_id');
    }

    public function hashtags(): HasMany
    {
        return $this->hasMany(ContentHashtag::class);
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }
}
