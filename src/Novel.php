<?php

namespace Haxibiao\Content;

use App\Traits\NovelAttrs;
use Haxibiao\Breeze\Model as BreezeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Novel extends BreezeModel
{
    protected $guarded = [];
    use HasFactory;
    use NovelAttrs;

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
