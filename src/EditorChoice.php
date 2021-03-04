<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorChoice extends BreezeModel
{
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
