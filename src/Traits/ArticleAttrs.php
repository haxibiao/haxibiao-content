<?php

namespace Haxibiao\Content\Traits;

use App\Category;
use App\SignUp;

trait ArticleAttrs
{
    public function getCountSignUpsAttribute()
    {
        if(!currentUser()){
            return 0;
        }
        return SignUp::where('user_id',getUserId())->where('signable_id',$this->id)->count();
    }

    public function getIsSignUpAttribute()
    {
        if(!currentUser()){
            return false;
        }
        $user = getUser();
        return SignUp::where('user_id',$user->id)->where('signable_id',$this->id)->count() > 0;
    }

    public function getIntroductionAttribute()
    {
        return $this->json->introduction;
    }

    public function getTimeAttribute()
    {
        return $this->json->time;
    }

    public function getAddressAttribute()
    {
        return $this->json->address;
    }
    
    public function getBodyAttribute()
    {
        //应该优先尊重本地body
        $local_body = data_get($this->attributes, 'body');
        if (blank($local_body)) {
            // 开启哈希云
            if (config('content.enable_haxiyun')) {
                // media database 获取body
                $cloud_body = optional(\DB::connection('media')->table('articles')
                        ->where([
                            'source_id' => $this->id,
                            'source'    => config('app.domain'),
                        ])
                        ->select('body')
                        ->first())
                    ->body;
                if (!is_null($cloud_body)) {
                    return $cloud_body;
                }
            }
        }
        return $local_body;
    }

    public function getSubjectAttribute()
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        //兼容视频文章
        if (empty($this->title) && !empty($this->description)) {
            return str_limit($this->description, 60);
        }
        return str_limit($this->body);
    }

    public function getSubjectDescriptionAttribute()
    {
        if (!empty($this->description)) {
            return $this->description;
        }

        return $this->subject;
    }

    public function getSummaryAttribute()
    {
        $description = $this->description;
        //body 已复用哈希云，这里性能风险，只能靠title description数据了
        if (empty($description) || strlen($description) < 2) {
            return $this->title;
        }
        return str_limit($description, 130);
    }

    public function getUrlAttribute()
    {
        $path = '/%s/%d';
        // if ($this->type == 'video') {
        //     return sprintf($path, $this->type, $this->video_id);
        // }
        $path = sprintf($path, 'article', $this->id);
        return seo_url($path);
    }

    public function hasImage()
    {
        return !empty($this->cover);
    }

    public function getPostTitle()
    {
        return $this->title ? $this->title : str_limit($this->body, $limit = 20, $end = '...');
    }

    public function getPivotCategoryAttribute()
    {
        return Category::find($this->pivot->category_id);
    }

    public function getPivotStatusAttribute()
    {
        return $this->pivot->submit;
    }

    public function getPivotTimeAgoAttribute()
    {
        return diffForHumansCN($this->pivot->created_at);
    }

    public function getCoversAttribute()
    {
        return $this->video ? $this->video->covers : null;
    }

    //兼容旧web api 的属性
    public function getHasImageAttribute()
    {
        return $this->image_id > 0 || !empty($this->cover);
    }

    public function getPrimaryImageAttribute()
    {
        return $this->cover;
    }

    //兼容大部分文章系统的封面URL逻辑，特殊情况的APP层覆盖本方法
    public function getCoverAttribute()
    {
        $cover_path = $this->cover_path;

        //已处理好的cdn地址(乐观更新的外部链接)
        if (filter_var($cover_path, FILTER_VALIDATE_URL)) {
            return $cover_path;
        }

        //cloud path 的
        $cover_path = parse_url($cover_path, PHP_URL_PATH);
        if (!blank($cover_path)) {
            return cdnurl($cover_path);
        }

        //尊重关联的媒体的封面
        if ($this->movie) {
            //电影剪辑
            return $this->movie->cover;
        }
        if ($this->video) {
            //短视频动态
            return $this->video->cover;
        }
        return null;
    }

    public function getVideoUrlAttribute()
    {
        return $this->video ? $this->video->url : null;
    }

    public function getFavoritedAttribute()
    {
        //借用favorable的特性属性
        return $this->is_favorited;
    }

    public function getFavoritedIdAttribute()
    {
        if ($user = getUser(false)) {
            $favorite = $user->favoritedArticles()->where('favorable_id', $this->id)->first();
            return $favorite ? $favorite->id : 0;
        }
        return 0;
    }

    public function getAnsweredStatusAttribute()
    {
        $issue = $this->issue;
        if (!$issue) {
            return null;
        }
        $resolutions = $issue->resolutions;
        if ($resolutions->isEmpty()) {
            return 0;
        }
        return 1;
    }

    public function getQuestionRewardAttribute()
    {
        if (!in_array($this->type, ['issue'])) {
            return 0;
        }
        $issue = $this->issue;
        if ($issue) {
            return $issue->gold;
        }
        return 0;
    }

    public function getScreenshotsAttribute()
    {
        $images      = $this->images;
        $screenshots = [];
        foreach ($images as $image) {
            $screenshots[] = ['url' => $image->url];
        }
        return json_encode($screenshots);
    }

    public function link()
    {
        //普通文章
        if ($this->type == 'article') {
            return $this->resoureTypeCN() . '<a href=' . $this->url . '>《' . $this->title . '》</a>';
        }
        //动态
        $title = str_limit($this->body, $limit = 50, $end = '...');
        if (empty($title)) {
            return '<a href=' . $this->url . '>' . $this->resoureTypeCN() . '</a>';
        }
        return $this->resoureTypeCN() . '<a href=' . $this->url . '>《' . $title . '》</a>';
    }
}
