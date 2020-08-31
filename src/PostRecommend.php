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

    //保存最后一次取完推荐的5-10个视频后，需要更新指针
    public function updateCursor($posts)
    {
        $reviewIds = $posts->pluck('review_id')->toArray();
        if (!blank($reviewIds)) {
            //拿到刚去到的一堆review_id里的最大的
            $maxReviewId = max($reviewIds);
            //获取里面的review_day信息
            $maxReviewDay = substr($maxReviewId, 0, 8);

            //用户所有日期的指针数组
            $dayReviewIds = $this->day_review_ids ?? [];
            $isExisted    = false;
            foreach ($dayReviewIds as &$dayReviewId) {
                $day = substr($dayReviewId, 0, 8);
                if ($day == $maxReviewDay && $maxReviewId > $dayReviewId) {
                    //更新你再这个日期的 指针
                    $dayReviewId = $maxReviewId;
                    $isExisted   = true;
                }
            }
            if (!$isExisted) {
                //增加你在这一天的指针
                $dayReviewIds[] = $maxReviewId;
            }

            //更新和保存回去这个数组
            $this->day_review_ids = array_unique($dayReviewIds);
            $this->save();
        }

        return $this;
    }
}
