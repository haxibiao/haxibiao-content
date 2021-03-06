<?php

namespace Haxibiao\Content;

use App\Site;
use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Traits\StickRepo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stick extends BreezeModel
{

    use StickRepo;

    const CHANNEL_OF_APP = 'APP'; // App频道
    const CHANNEL_OF_PC  = 'WEB'; // 网站频道

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
