<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stick extends BreezeModel
{
    public function editorChoice(): BelongsTo
    {
        return $this->belongsTo(EditorChoice::class);
    }

    public function stickable(): MorphTo
    {
        return $this->morphTo('stickable');
    }
}
