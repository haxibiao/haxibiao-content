<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;

class PostRecommended extends Model
{
    protected $table = 'posts_recommended';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
