<?php

namespace haxibiao\content\Traits;

use App\Favorite;


trait FavoriteRepo
{
    /**
     * 切换收藏的状态
     *
     * @param $id
     * @param $type
     * @return mixed
     */
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

    public static function getFavoritesQuery($favorable_type)
    {
        $user = getUser();
        $qb   = $user->favorites()
            ->with('favorable')
            ->where('favorable_type', $favorable_type)
            ->latest('id');

        //FIXME: 已下架，已拒绝的动态或者题目不允许从收藏列表里显示？ 直接显示状态(已下架,已拒绝即可)，前端可以不允许点进去详细页即可
        //隐藏掉已下架的资源，不让收藏过的用户再看到
        // $removeFavoriteIds = [];
        // $favoritesCollection->filter(function ($favorite) use (&$removeFavoriteIds) {
        //     //除了用户待审的和已上架,已拒绝的，题目都不出现在别人的收藏列表
        //     if (!($favorite->favorable->isPublish() || $favorite->favorable->isReviewing() || $favorite->favorable->isRefused())) {
        //         $removeFavoriteIds[] = $favorite->id;
        //         return false;
        //     }
        //     return $favorite;
        // });
        // Favorite::destroy($removeFavoriteIds);
        return $qb;
    }
}
