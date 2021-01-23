<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait UsePost
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function douyinPosts(): HasMany
    {
        return $this->hasMany(Post::class)->whereNotNull('spider_id');
    }
}
