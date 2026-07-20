<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandVoiceSetting extends Model
{
    protected $fillable = [
        'brand_id', 'tone_style', 'company_description',
        'products_services', 'target_audience', 'keywords', 'avoid_words',
    ];

    protected function casts(): array
    {
        return ['keywords' => 'array', 'avoid_words' => 'array'];
    }
}
