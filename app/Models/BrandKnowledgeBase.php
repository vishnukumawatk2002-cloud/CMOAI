<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandKnowledgeBase extends Model
{
    protected $table = 'brand_knowledge_bases';

    protected $fillable = [
        'brand_id', 'detected_tone', 'detected_audience', 'detected_services',
        'top_keywords', 'source_data', 'training_status', 'last_trained_at', 'training_error',
    ];

    protected function casts(): array
    {
        return [
            'top_keywords' => 'array',
            'source_data' => 'array',
            'last_trained_at' => 'datetime',
        ];
    }
}
