<?php

namespace Haxibiao\Content;

use App\Model;
use Haxibiao\Cms\Traits\PlayWithCms;
use Haxibiao\Content\Traits\IssueAttrs;
use Haxibiao\Content\Traits\IssueResolvers;
use Haxibiao\Content\Traits\WithCategory;
use Haxibiao\Media\Image;
use Haxibiao\Media\Traits\WithImage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use IssueResolvers;
    use IssueAttrs;
    use SoftDeletes;
    use WithImage;
    use WithCategory;
    use PlayWithCms;

    protected $guarded = [];

    public function getMorphClass()
    {
        return 'issues';
    }

    public function article()
    {
        return $this->hasOne(\App\Article::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function solutions()
    {
        return $this->hasMany(\App\Solution::class)->orderBy('id', 'desc');
    }

    //兼容重构前的resolution
    public function resolutions()
    {
        return $this->hasMany(\App\Solution::class)->orderBy('id', 'desc');
    }

    public function latestResolution()
    {
        return $this->belongsTo(\App\Solution::class, 'latest_resolution_id');
    }

    public function bestResolution()
    {
        return $this->belongsTo(\App\Solution::class, 'best_resolution_id');
    }

    public function isPay()
    {
        return $this->bonus > 0;
    }

    public function leftHours()
    {
        $left = 48;
        $left = $this->created_at->addDays($this->deadline)->diffInHours(now());
        return $left;
    }

    public function isAnswered()
    {
        return !empty($this->selectedAnswers());
    }

    public function selectedAnswers()
    {
        $answers = [];
        if (!empty($this->resolution_ids)) {
            $resolution_ids = explode(',', $this->resolution_ids);
            $answers        = $this->resolutions()->whereIn('id', $resolution_ids)->get();
        }
        return $answers;
    }

    public function relateImage()
    {
        //有最新回答，先用回答里的图片
        if ($this->latestResolution && !empty($this->latestResolution->image_url)) {
            $image_url = $this->latestResolution->image_url;
            $image     = Image::where('path', $image_url)->first();
            if ($image) {
                $image_url = $image->thumbnail;
            }
            return $image_url;
        }
        //没有，只好用问题里的图片
        return data_get($this, 'image1', null);
    }

    public function link()
    {
        return '<a href="/issue/' . $this->id . '">' . $this->title . '</a>';
    }
    public function result()
    {
        return $this->resolution_ids ? '已经结账' : '无人回答已经退回余额';
    }

}
