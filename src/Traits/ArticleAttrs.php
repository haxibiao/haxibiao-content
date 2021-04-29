<?php

namespace Haxibiao\Content\Traits;

use App\Category;

trait ArticleAttrs
{
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

        if (strpos($cover_path, 'https') !== false) {
            return $cover_path;
        }
        //避免旧http cdn url 的混合内容问题
        if (strpos($cover_path, 'http') !== false) {
            return str_replace('http', 'https', $cover_path);
        }

        //为空返回默认图片
        if (empty($cover_path)) {
            if ($this->type == 'article') {
                //FIXME: 返回null兼容has_image 等旧文章系统attrs的判断，重构清理has_image代码后删除
                return null;
            }
            if ($this->movie) {
                //电影剪辑
                return $this->movie->cover;
            }
            if ($this->video) {
                //短视频动态
                return $this->video->cover;
            }
            return url("/images/cover.png");
        }

        //文章的图片都应该已存cos,没有的修复文件+数据, 强制返回cdn全https url，兼容多端
        $cover_path = parse_url($cover_path, PHP_URL_PATH);
        return cdnurl($cover_path);
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
