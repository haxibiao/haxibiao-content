<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Choiceable extends Pivot
{
    protected $guarded = [
    ];

    protected $table = 'choiceables';

    public function choiceable()
    {
        return $this->morphTo('choiceable');
    }

}
