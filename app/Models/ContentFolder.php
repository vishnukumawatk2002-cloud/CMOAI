<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentFolder extends Model
{
    protected $fillable = ['brand_id', 'name', 'slug', 'sort_order'];
}
