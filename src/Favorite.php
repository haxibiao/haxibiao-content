<?php

namespace haxibiao\content;

use App\Post;
use App\User;
use App\Model;
use App\Article;
use haxibiao\content\Traits\FavoriteRepo;
use haxibiao\content\Traits\FavoriteResolvers;

class Favorite extends Model
{
    use FavoriteRepo;
    use FavoriteResolvers;

    protected $fillable = [
        'user_id',
        'faved_id',
        'faved_type',
    ];


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
