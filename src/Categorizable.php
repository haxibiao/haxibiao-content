<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;

class Categorizable extends Model
{
    protected $table = 'categorizables';

    protected $guarded = [];

    public function categorizable()
    {
        return $this->morphTo();
    }
}
