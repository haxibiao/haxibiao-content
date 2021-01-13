<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;

class Taggable extends Model
{

    protected $table = 'taggables';
    public $guarded  = [];

    public function taggable()
    {
        return $this->morphTo();
    }
}
