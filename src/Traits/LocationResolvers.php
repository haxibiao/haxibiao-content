<?php

namespace Haxibiao\Content\Traits;

use App\Location;
use App\Dimension;

trait LocationResolvers
{
    public function recordLoginLocation($root, $args, $context, $info)
    {
        $district = data_get($args, 'location.district');
        $user = getUser();
        //记录登陆地区统计数据
        Dimension::setDimension($user,'登录地区:'.$district);
        //更新用户位置数据
        Location::storeLocation(data_get($args, 'location'), 'users',$user->id);
        return true;
    }
}
