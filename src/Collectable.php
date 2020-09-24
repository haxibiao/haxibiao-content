<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;

class Collectable extends Model
{
    protected $table = 'collectables';

    protected $fillable = [
        'user_id',
        'collection_id',
        'collection_name',
        'collectable_id',
        'collectable_type',
    ];

//    public function collectable()
//    {
//        return $this->morphTo();
//    }
}
