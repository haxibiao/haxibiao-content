<?php

namespace Haxibiao\Content;

use Illuminate\Database\Eloquent\Model;
use Haxibiao\Content\Traits\LocationRepo;
use Haxibiao\Content\Traits\LocationResolvers;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Location extends Model
{
    use LocationResolvers;
    use LocationRepo;
    //
    protected $fillable = [
        'address',
        'description',
        'district',
        'latitude',
        'longitude',
        'geo_code',
        'post_id',
    ];

    //地球半径系数
    const EARTH_RADIUS = 6370.996;
    const PI = 3.1415926;
    
    public function post()
    {
        return $this->belongsTo(\App\Post::class);
    }
    public function located(): MorphTo
    {
        return $this->morphTo();
    }
}
