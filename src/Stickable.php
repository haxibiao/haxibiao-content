<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;

class Stickable extends Model
{
    protected $guarded = [];

    const CHANNEL_OF_APP    = 'APP';// App频道
    const CHANNEL_OF_PC     = 'WEB'; // 网站频道

    public function item()
    {
        return $this->morphTo('stickable');
    }

    public function getSubjectAttribute()
    {
        return $this->name;
    }

    public static function items($sticks)
    {
        $result = [];
        foreach ($sticks as $stick) {
            $result[] = $stick->item;
        }
        return $result;
    }
}
