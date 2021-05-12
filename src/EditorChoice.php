<?php

namespace Haxibiao\Content;

use App\Stick;
use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditorChoice extends BreezeModel
{
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sticks(): HasMany
    {
        return $this->hasMany(Stick::class);
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

}
