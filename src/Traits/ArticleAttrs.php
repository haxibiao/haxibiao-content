<?php

namespace Haxibiao\Content\Traits;

use App\Category;

trait ArticleAttrs
{
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
        if (empty($description) || strlen($description) < 2) {
            $body        = html_entity_decode($this->body);
            $description = str_limit(strip_tags($body), 130);
        }
        return str_limit($description, 130);
    }

    public function getUrlAttribute()
    {
        $path = '/%s/%d';
        if ($this->type == 'video') {
            return sprintf($path, $this->type, $this->video_id);
        }
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
        $cover_url = $this->cover_path;
        //为空返回默认图片
        if (empty($cover_url)) {
            if ($this->type == 'article') {
                //返回null兼容has_image 等旧文章系统attrs的判断
                return null;
            }
            return url("/images/cover.png");
        }

        //文章的图片都应该已存cos,没有的修复文件+数据, 强制返回cdn全https url，兼容多端
        $cover_path = parse_url($cover_url, PHP_URL_PATH);
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

    public function getCountLikesAttribute()
    {
        return $this->likes()->count();
    }
}
