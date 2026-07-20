<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id', 'platform', 'account_name', 'account_handle',
        'account_type', 'external_id', 'follower_count', 'profile_image_url',
        'status', 'connected_at', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function oauthToken(): HasOne
    {
        return $this->hasOne(OauthToken::class);
    }
}
