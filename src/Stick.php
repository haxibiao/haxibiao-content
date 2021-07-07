<?php

namespace Haxibiao\Content;

use App\Site;
use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Traits\StickRepo;
use Haxibiao\Content\Traits\StickResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            '每日推荐',
            '精选美剧',
            '精选韩剧',
            '精选日剧',
            '精选港剧',
        ];
    }

    public function editorChoice()
    {
        return $this->belongsTo("App\EditorChoice");
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
