<?php

namespace Haxibiao\Content;

use App\Article;
use App\Model;
use App\Post;
use App\User;
use Haxibiao\Content\Traits\FavoriteRepo;
use Haxibiao\Content\Traits\FavoriteResolvers;

class Favorite extends Model
{
    use FavoriteRepo;
    use FavoriteResolvers;

    protected $guarded = [];

    public function faved()
    {
        return $this->morphTo();
    }

    public function article()
    {
        return $this->belongsTo(\App\Article::class, 'faved_id');
    }

    public function post()
    {
        return $this->belongsTo(\App\Post::class, 'faved_id');
    }

    public function user()
    {
        $this->belongsTo(\App\User::class);
    }

    //actionable target, 比如 活动记录 - 收藏了 - 对象(文章，用户等)
    public function target()
    {
        return $this->morphTo();
    }
}
