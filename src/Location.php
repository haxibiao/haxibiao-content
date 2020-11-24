<?php

namespace Haxibiao\Content;

use Haxibiao\Content\Traits\LocationRepo;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use LocationRepo;
    //
    protected $fillable = [
        'address',
        'description',
        'latitude',
        'longitude',
        'geo_code',
        'post_id',
    ];
    public function post()
    {
        return $this->belongsTo(\App\Post::class);
    }
}
