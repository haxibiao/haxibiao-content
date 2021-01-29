<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends BreezeModel
{
    protected $guarded = [];
    use HasFactory;

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }
}
