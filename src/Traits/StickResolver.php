<?php

namespace Haxibiao\Content\Traits;

use App\EditorChoice;
use App\Stick;

trait StickResolver
{
    public function resolveTodayRecommend()
    {
        $editor = EditorChoice::where('title', '今日推荐')->first();
        // 数量不多，in random order 解决每次返回的数据不同
        if ($editor) {
            return Stick::where('editor_choice_id', $editor->id)->inRandomOrder()->take(4)->get();
        } else {
            return Stick::inRandomOrder()->take(4)->get();
        }
    }
}
