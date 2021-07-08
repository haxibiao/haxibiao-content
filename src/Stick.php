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
    //前端根据place获取App置顶位置stick数组
    //再逐一嗯就stick id获取置顶（movies｜posts｜questions）
    //此处place表示范围较大，为App某页位置（影厅、社区、博客、合集）
    public static function getAppPlaces()
    {
        return [
            '影厅',
            '合集',
            '社区',
            '博客',
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
