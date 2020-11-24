<?php

namespace Haxibiao\Content\Traits;

use App\Location;
use Sk\Geohash\Geohash;

trait LocationRepo
{

    public static function storeLocation(array $locationInfo, $located_type,$located_id)
    {
        $location           = Location::create($locationInfo);
        $geohash            = new Geohash();
        $geoCode            = $geohash->encode(data_get($locationInfo, 'latitude'), data_get($locationInfo, 'longitude'), 12);
        $location->geo_code = $geoCode;
        $location->located_id  = $located_id;
        $location->located_type  = $located_type;
        $location->save();
        return $location;
    }

    public function getNearbyPost($longitude,$latitude)
    {
        # code...
    }
}
