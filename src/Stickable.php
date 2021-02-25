<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;

class Stickable extends Model
{
    protected $guarded = [];

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
