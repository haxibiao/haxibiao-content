<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;

class Taggable extends Model
{
    use HasFactory;

    protected $table = 'taggables';
    public $guarded  = [];

    public function taggable()
    {
        return $this->morphTo();
    }
}
