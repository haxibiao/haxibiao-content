<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Task\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait CategoryAttrs
{
    public function canAdmin()
    {
        if (!Auth::check()) {
            return false;
        }
        $is_category_admin = in_array(Auth::id(), $this->admins->pluck('id')->toArray());
        return $is_category_admin || checkEditor();
    }

    public function isFollowed()
    {
        return Auth::check() && Auth::user()->isFollow('categories', $this->id);
    }

    public function checkAdmin()
    {
        return Auth::check() && $this->isAdmin(Auth::user());
    }

    public function isAdmin($user)
    {
        if ($this->admins()->where('users.id', $user->id)->count() || $this->user_id == $user->id) {
            return true;
        }
        return false;
    }

    public function description()
    {
        return empty($this->description) ? '这专题管理很懒，一点介绍都没留下...' : $this->description;
    }

    public function getUrlAttribute()
    {
        return '/category/' . $this->id;
    }

    public function getCanEditAttribute()
    {
        if ($user_id = getUserId()) {
            return $this->user_id == $user_id;
        }
        return false;
    }

    /**
     * 专题的APP内部图标定制 - 编辑权限上传
     */
    public function getLogoAppAttribute()
    {
        $logo_path = parse_url($this->getRawOriginal('logo_app') ?? '', PHP_URL_PATH);
        return cdnurl($logo_path);
    }

    /**
     * 专题封面
     */
    public function getLogoUrlAttribute()
    {
        $logo        = $this->getRawOriginal('logo');
        $defaultLogo = url('images/collection.png');
        if (is_null($logo)) {
            return $defaultLogo;
        }
        if (str_contains($logo, 'http')) {
            return $logo;
        } else {
            return cdnurl($logo);
        }
    }

    /**
     * 专题封面，只返回url
     */
    public function getLogoAttribute()
    {
        return $this->logo_url;
    }

    public function getFollowIdAttribute()
    {
        if ($user = currentUser()) {
            $follow = $user->followings()->where('followable_type', 'categories')->where('followable_id', $this->id)->first();
            return $follow ? $follow->id : 0;
        }
        return 0;
    }

    public function getFollowedAttribute()
    {
        return $this->follow_id ? 1 : 0;
    }

    public function getTopAdminsAttribute()
    {
        $admins = $this->admins()->take(8)->get();
        $owner  = $this->user;
        if ($owner) {
            return $admins->merge([$owner]);
        }
        return $admins;
    }

    public function getTopAuthorsAttribute()
    {
        return $this->authors()->take(9)->get();
    }

    public function getIconAttribute()
    {
        $icon = $this->getRawOriginal('icon');
        if (!$icon) {
            $icon = $this->getRawOriginal('logo');
        }
        return $icon;
    }

    public function getCanAuditAttribute()
    {
        //不是官方题库，并且后台标记可以审题
        return !$this->is_official && $this->attributes['can_audit'];
    }

    public function getCanReviewCountAttribute()
    {
        return $this->users()->where('correct_count', '>', 100)->count();
    }

    public function getIconUrlAttribute()
    {
        if (starts_with($this->icon, "http")) {
            return $this->icon;
        }

        if (empty($this->icon)) {
            return config('app.cos_url') . '/storage/app/avatars/avatar.png';
        }
        return Storage::disk('public')->url($this->icon);
    }

    public function getShieldingAdAttribute()
    {
        if ($user = currentUser()) {
            return Item::shieldingCategoryAd($user->id, $this->id);
        }
    }

    public function getUserCanSubmitAttribute()
    {
        if ($user = currentUser()) {
            return $this->userCanSubmit($user);
        }
    }

    public function getUserCanAuditAttribute()
    {
        if ($user = currentUser()) {
            return $this->userCanAudit($user);
        }
    }

    public function getAnswerCountAttribute()
    {
        if ($user = currentUser()) {
            return $this->answerCount($user);
        }
        return 0;
    }

}
