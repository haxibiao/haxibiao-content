<?php

namespace Haxibiao\Content;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PostRecommend extends Model
{
    protected $guarded = [];

    protected $casts = [
        'day_review_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 返回用户的刷视频推荐范围
     *
     * @param User $user
     * @param string $scope
     * @return PostRecommend
     */
    public static function fetchByScope($user, $scope = null)
    {
        return PostRecommend::firstOrCreate([
            'user_id' => $user->id,
            'scope'   => $scope,
        ]);
    }
}
