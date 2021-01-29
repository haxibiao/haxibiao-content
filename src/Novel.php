<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Content\Traits\NovelAttrs as TraitsNovelAttrs;
use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Novel extends BreezeModel
{
    protected $guarded = [];
    use HasFactory;
    use TraitsNovelAttrs;

    public const STATUS_CRAWL_DONE = 2;
    public const STATUS_WATING     = 0;
    public const STATUS_DISABLE    = -1;

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_CRAWL_DONE);
    }
}
