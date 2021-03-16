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
}
