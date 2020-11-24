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
    public function post()
    {
        return $this->belongsTo(\App\Post::class);
    }
    public function located(): MorphTo
    {
        return $this->morphTo();
    }
}
