<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siteable extends Model
{
    use HasFactory;

    protected $table = 'siteables';
    public $guarded  = [];

    public function siteable()
    {
        return $this->morphTo();
    }
}
