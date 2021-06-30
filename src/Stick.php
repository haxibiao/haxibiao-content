<?php

namespace Haxibiao\Content;

use App\Site;
use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Traits\StickRepo;
use Haxibiao\Content\Traits\StickResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stick extends BreezeModel
{

    use StickRepo, StickResolver;

    const CHANNEL_OF_APP = 'APP'; // App频道
    const CHANNEL_OF_PC  = 'WEB'; // 网站频道

    //APP内置顶位置
    public static function getAppPlaces()
    {
        return [
            '影厅顶部',
            '合集顶部',
            '今日推荐',
            '精选韩剧',
            '精选日剧',
            '精选港剧',
        ];
    }
    public function editorChoice(): BelongsTo
    {
        return $this->belongsTo(EditorChoice::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function stickable(): MorphTo
    {
        return $this->morphTo('stickable');
    }
}
