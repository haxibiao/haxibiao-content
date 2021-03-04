<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stick extends BreezeModel
{

    const CHANNEL_OF_APP = 'APP'; // App频道
    const CHANNEL_OF_PC  = 'WEB'; // 网站频道

    public function editorChoice(): BelongsTo
    {
        return $this->belongsTo(EditorChoice::class);
    }

    public function stickable(): MorphTo
    {
        return $this->morphTo('stickable');
    }
}
