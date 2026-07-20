<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledPost extends Model
{
    protected $fillable = [
        'content_item_id', 'social_account_id', 'scheduled_at', 'status',
        'published_at', 'external_post_id', 'external_post_url',
        'failure_reason', 'retry_count', 'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function contentItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function socialAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
