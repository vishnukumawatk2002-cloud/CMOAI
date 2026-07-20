<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGenerationRequest extends Model
{
    protected $fillable = [
        'brand_id', 'user_id', 'content_type', 'platforms',
        'prompt', 'status', 'error_message', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }
}
