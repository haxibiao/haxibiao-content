<?php

namespace Haxibiao\Content\Traits;

use App\Location;
use Sk\Geohash\Geohash;

trait LocationRepo
{

    public static function storeLocation(array $locationInfo, $post_id)
    {
        $location           = Location::create($locationInfo);
        $geohash            = new Geohash();
        $geoCode            = $geohash->encode(data_get($locationInfo, 'latitude'), data_get($locationInfo, 'longitude'), 12);
        $location->geo_code = $geoCode;
        $location->post_id  = $post_id;
        $location->save();
        return $location;
    }
}
