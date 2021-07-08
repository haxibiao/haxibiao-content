<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Choiceable extends Pivot
{
    protected $guarded = [
    ];

    public function choiceable()
    {
        return $this->morphTo('choiceable');
    }

}
