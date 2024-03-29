<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorChoice extends BreezeModel
{

    public function sticks()
    {
        return $this->belongstoMany('App\Stick', 'stick_place')->withTimestamps();
    }

    public function movies()
    {
        return $this->choiceable('App\Movie');
    }

    public function activities()
    {
        return $this->choiceable('App\Activity');
    }

    public function collections()
    {
        return $this->choiceable('App\Collection');
    }

    public function choiceable($related)
    {
        return $this->morphedByMany($related, 'choiceable')->withTimestamps();
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveIndexEditorChoice($root, $args, $content, $info)
    {
        return self::all();
    }

    public function resolveEditorChoice($root, $args, $content, $info)
    {
        //部分app小编精选显示标记收藏状态
        //标记获取详情数据信息模式
        request()->request->add(['fetch_sns_detail' => true]);

        $title = data_get($args, 'title');
        return self::where('title', $title)->first();
    }

    public function resolveMovies($root, $args, $content, $info)
    {
        return $root->movies()->where('has_playurl','1')->orderBy('rank', 'desc');
    }

    public function resolveActivities($root, $args, $content, $info)
    {
        return $root->activities();
    }

    public function resolveCollections($root, $args, $content, $info)
    {
        return $root->collections();
    }

}
