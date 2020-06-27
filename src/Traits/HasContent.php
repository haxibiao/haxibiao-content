<?php

namespace haxibiao\content\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户在content系统里的特性
 */
trait HasContent
{
    public function posts(): HasMany
    {
        return $this->hasMany(\haxibiao\content\Post::class);
    }
}
