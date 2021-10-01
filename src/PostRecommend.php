<?php

namespace Haxibiao\Content;

use App\User;
use Haxibiao\Content\Post;
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
     * 获取用户已推荐过的排重的日子
     * @return array
     */
    public function getReviewDaysAttribute()
    {
        $reviewDays = [];
        foreach ($this->day_review_ids ?? [] as $userDayReviewId) {
            $reviewDay    = substr($userDayReviewId, 0, 8);
            $reviewDays[] = $reviewDay;
        }
        return array_unique($reviewDays);
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

    /**
     * 获取下一个review_id
     *
     * @param array $maxReviewIdInDays
     * @return int
     */
    public function getNextReviewId($maxReviewIdInDays)
    {
        return Post::getNextReviewId($this->day_review_ids, $maxReviewIdInDays);
    }

    /**
     * 随机重置某个review_day
     *
     * @return int
     */
    public function resetReviewDayByRandom()
    {
        $reviewDays = $this->reviewDays;
        $daysCnt    = count($reviewDays);
        if ($daysCnt > 0) {
            $randomResetReviewDay = $reviewDays[mt_rand(0, $daysCnt - 1)];
            $this->delReviewDay($randomResetReviewDay, true);
        }

        return $randomResetReviewDay;
    }

    /**
     * 删除review_day
     *
     * @param int $delReviewDay
     * @param boolean $isSave
     * @return boolean
     */
    public function delReviewDay($delReviewDay, $isSave = false)
    {
        $isDeleted    = false;
        $dayReviewIds = $this->day_review_ids;
        foreach ($dayReviewIds as $key => $userDayReviewId) {
            $reviewDay = substr($userDayReviewId, 0, 8);
            if ($delReviewDay == $reviewDay) {
                unset($dayReviewIds[$key]);
                $isDeleted = true;
            }
        }
        $this->day_review_ids = $dayReviewIds;
        if ($isSave) {
            $this->save();
        }

        return $isDeleted;
    }
}
