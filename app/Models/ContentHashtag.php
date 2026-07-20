<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentHashtag extends Model
{
    protected $fillable = ['content_item_id', 'hashtag'];
}
