<?php

namespace Haxibiao\Content\Traits;

use App\User;
use Haxibiao\Content\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait CategoryRepo
{
    public function fillForJs()
    {
        $this->url         = $this->url;
        $this->logo        = $this->logoUrl;
        $this->description = $this->description();
    }

    /**
     * 添加专题管理员
     */
    public function addAdmin(User $user)
    {
        $this->admins()->syncWithoutDetaching([
            $user->id => ['is_admin' => 1],
        ]);
    }

    /**
     * 添加专题编辑作者
     */
    public function addAuthor(User $user)
    {
        $this->authors()->syncWithoutDetaching([
            $user->id,
        ]);
    }

    public function isCreator($admin)
    {
        return $admin->id == $this->user_id;
    }

    public function topAdmins()
    {
        $topAdmins = $this->admins()->orderBy('id', 'desc')->take(10)->get();
        return $topAdmins;
    }

    public function topAuthors()
    {
        $topAuthors = $this->authors()->orderBy('id', 'desc')->take(8)->get();
        return $topAuthors;
    }

    public function topFollowers()
    {
        $topFollows   = $this->follows()->orderBy('id', 'desc')->take(8)->get();
        $topFollowers = [];
        foreach ($topFollows as $follow) {
            $topFollowers[] = $follow->user;
        }
        return $topFollowers;
    }

    /**
     * 保存专题的2种logo?
     */
    public function saveLogo(Request $request)
    {
        $name = $this->id . '_' . time();
        if ($request->logo) {
            $file                = $request->file('logo');
            $extension           = $file->getClientOriginalExtension();
            $file_name_formatter = $name . '.%s.' . $extension;
            $file_name_big       = sprintf($file_name_formatter, 'logo');

            //裁剪180
            $tmp_big = '/tmp/' . $file_name_big;
            $img     = Image::make($file->path());
            $img->fit(180);
            $img->save($tmp_big);
            $cloud_path = 'storage/app-' . env('APP_NAME') . '/category/' . $file_name_big;
            Storage::put($cloud_path, @file_get_contents($tmp_big));
            $this->logo = $cloud_path;

            //裁剪32 兼容web
            $file_name_small = sprintf($file_name_formatter, 'logo.small');
            $tmp_small       = '/tmp/' . $file_name_small;
            $img_small       = Image::make($tmp_big);
            $img_small->fit(32);
            $img_small->save($tmp_small);
            $cloud_path = 'storage/app-' . env('APP_NAME') . '/category/' . $file_name_small;
            Storage::put($cloud_path, @file_get_contents($tmp_small));
        }

        if ($request->logo_app) {
            $file                = $request->file('logo_app');
            $extension           = $file->getClientOriginalExtension();
            $file_name_formatter = $name . '.%s.' . $extension;
            $file_name_big       = sprintf($file_name_formatter, 'logo');

            //裁剪180
            $tmp_big = '/tmp/' . $file_name_big;
            $img     = Image::make($file->path());
            $img->fit(180);
            $img->save($tmp_big);

            //区分APP的storage目录，支持多个APP用一个bucket
            $cloud_path = 'storage/app-' . env('APP_NAME') . '/category/' . $file_name_big;
            Storage::put($cloud_path, file_get_contents($tmp_big));
            $this->logo = $cloud_path;
        }
    }

    public function recordBrowserHistory()
    {
        //记录浏览历史
        if (currentUser()) {
            $user = getUser();
            //如果重复浏览只更新纪录的时间戳
            $visited = \App\Visit::firstOrNew([
                'user_id'      => $user->id,
                'visited_type' => 'categories',
                'visited_id'   => $this->id,
            ]);
            $visited->updated_at = now();
            $visited->save();
        }
    }

    public static function getTopCategory($number = 5)
    {
        $data             = [];
        $ten_top_category = Category::select(DB::raw('count(*) as categoryCount,category_id'))
            ->from('articles')
            ->whereNotNull('video_id')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderBy('categoryCount', 'desc')
            ->take($number)->get()->toArray();

        foreach ($ten_top_category as $top_category) {
            $cate           = Category::find($top_category["category_id"]);
            $data['name'][] = $cate ? $cate->name : '空';
            $data['data'][] = $top_category["categoryCount"];
        }
        return $data;
    }

    public static function getTopLikeCategory($number = 5)
    {
        $data = [];

        $ten_top_category = Category::select(DB::raw('sum(count_likes) as categoryCount,category_id'))
            ->from('articles')
            ->whereNotNull('video_id')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderBy('categoryCount', 'desc')
            ->take($number)->get()->toArray();

        foreach ($ten_top_category as $top_category) {
            $cate              = Category::find($top_category["category_id"]);
            $data['options'][] = $cate ? $cate->name : '空';
            $data['value'][]   = $top_category["categoryCount"];
        }
        return $data;
    }
}
