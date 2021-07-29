<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Location;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Type\Decimal;
use Sk\Geohash\Geohash;

trait LocationRepo
{

    public static function storeLocation(array $locationInfo, $located_type, $located_id)
    {
        $location               = Location::create($locationInfo);
        $geohash                = new Geohash();
        $geoCode                = $geohash->encode(data_get($locationInfo, 'latitude'), data_get($locationInfo, 'longitude'), 12);
        $location->geo_code     = $geoCode;
        $location->located_id   = $located_id;
        $location->located_type = $located_type;
        $location->save();
        return $location;
    }

    public static function getNearbyPostIds($user)
    {
        if (empty($user->location)) {
            return [];
        }
        $longitude = $user->location->longitude;
        $latitude  = $user->location->latitude;
        if ($longitude && $latitude) {
            return Location::select(DB::raw('*,ACOS(SIN(' . $latitude . ' *' . Location::PI . ' / 180) * SIN(latitude * ' . Location::PI . ' / 180) +
            COS( ' . $latitude . ' * ' . Location::PI . ' / 180) *
            COS(latitude * ' . Location::PI . ' / 180) *
            COS(' . $longitude . ' * ' . Location::PI . ' / 180 - longitude * ' . Location::PI . ' / 180))* ' . Location::EARTH_RADIUS . '
            as distance '))
                ->where('located_type', 'posts')
            //50公里内都算附近
                ->having(DB::raw('distance'), '<', '50000')
                ->orderBy(DB::raw('distance'))
                ->take(20)
                ->get()
                ->pluck('located_id')
                ->toArray();
        } else {
            return [];
        }

    }

    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度
     * @param  Decimal $latitude1  起点纬度
     * @param  Decimal $longitude2 终点经度
     * @param  Decimal $latitude2  终点纬度
     * @param  Int     $unit       单位 1:米 2:公里
     * @param  Int     $decimal    精度 保留小数位数
     * @return Decimal
     */
    public static function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
    {

        $radLat1 = $latitude1 * Location::PI / 180.0;
        $radLat2 = $latitude2 * Location::PI / 180.0;

        $radLng1 = $longitude1 * Location::PI / 180.0;
        $radLng2 = $longitude2 * Location::PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * Location::EARTH_RADIUS * 1000;

        if ($unit == 2) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }
}
