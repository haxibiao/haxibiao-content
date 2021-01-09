<?php

namespace Haxibiao\Content\Traits;

use App\Post;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户使用content系统
 */
trait UseContent
{
    use UseCategory;
    use UseArticle;

    //with post
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function douyinPosts(): HasMany
    {
        return $this->hasMany(Post::class)->whereNotNull('spider_id');
    }
}