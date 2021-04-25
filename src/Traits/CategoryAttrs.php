<?php

namespace Haxibiao\Content\Traits;

use Illuminate\Support\Facades\Auth;

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

    public function logo_app()
    {
        return cdnurl($this->logo_app);
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

    public function link()
    {
        return '<a href="/category/' . $this->id . '">' . $this->name . '</a>';
    }

    public function getCanEditAttribute()
    {
        if ($user_id = getUserId()) {
            return $this->user_id == $user_id;
        }
        return false;
    }

    public function getLogoUrlAttribute()
    {
        $logo         = $this->logo;
        $defaultImage = config('haxibiao-content.collection_default_logo');
        if (is_null($logo)) {
            return $defaultImage;
        }
        if (str_contains($logo, 'http')) {
            return $logo;
        } else {
            return cdnurl($logo);
        }
    }

    // 小图标
    public function getIconUrlAttribute()
    {
        $logo = $this->logo;
        // 存URL的是云资源同步，没裁剪小图
        if (str_contains($logo, 'http')) {
            return $this->logoUrl;
        }
        return str_replace('.logo.jpg', '.logo.small.jpg', $this->logoUrl);
    }

    public function getFollowIdAttribute()
    {
        if ($user = getUser(false)) {
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
}
