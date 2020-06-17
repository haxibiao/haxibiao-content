<?php

namespace haxibiao\content\Traits;

use App\Favorite;


trait FavoriteRepo
{
    public static function toggleFavorite($id, $type)
    {
        $favorite = Favorite::firstOrNew([
            'user_id'    => getUser()->id,
            'faved_id'   => $id,
            'faved_type' => $type
        ]);
        //取消收藏
        if ($favorite->id) {
            $favorite->delete();
            $favorite->favorited = false;
        } else {
            $favorite->save();
            $favorite->favorited = true;
        }
        return $favorite;
    }
}
