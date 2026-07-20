<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'file_name',
        'file_path',
        'disk',
        'file_type',
        'mime_type',
        'file_size',
        'status',
        'indexed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'indexed_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
