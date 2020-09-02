<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户在content系统里的特性
 */
trait HasContent
{
    use HasCategory;
    use HasArticle;

    public function postableModel(): string
    {
        return config('haxibiao-content.models.post');
    }

    public function posts(): HasMany
    {
        return $this->hasMany($this->postableModel());
    }

    public function douyinPosts(): HasMany
    {
        return $this->hasMany($this->postableModel())->whereNotNull('spider_id');
    }
}
