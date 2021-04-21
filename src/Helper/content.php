<?php

use App\Article;
use App\Category;
use App\Movie;
use Haxibiao\Content\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('content_path')) {
    function content_path($path)
    {
        return __DIR__ . "/../../" . $path;
    }
}

/**
 * 首页置顶电影
 */
function indexTopMovies($top = 4)
{
    return Movie::latest('updated_at')->take($top)->get();
}

/**
 * 首页置顶视频
 */
function indexTopVideos($top = 4)
{
    return Post::has('video')
        ->publish()
        ->latest('updated_at')
        ->take($top)
        ->get();
}

/**
 * 首页的专题
 * @return [category] [前几个专题的数组]
 */
function indexTopCategories($top = 7)
{
    //首页推荐分类
    $stick_categories    = get_stick_categories();
    $stick_categorie_ids = array_pluck($stick_categories, 'id');
    $top_count           = $top - count($stick_categories);

    //已登录,专题的获取顺序为：置顶的专题>关注的专题>官方大专题
    if (Auth::check()) {
        $user = Auth::user();

        //获取所有关注的专题
        $all_follow_category_ids = \DB::table('follows')->where('user_id', $user->id)
            ->where('followable_type', 'categories')
            ->whereNotIn('follows.followable_id', $stick_categorie_ids)
            ->whereExists(function ($query) {
                return $query->from('categories')
                    ->whereRaw('categories.id = follows.followable_id')
                    ->where('categories.status', '>', Category::STATUS_DRAFT)
                    ->where('categories.is_official', 0);
            })->take($top_count)->pluck('followable_id')->toArray();
        $category_ids = array_merge($stick_categorie_ids, $all_follow_category_ids);

        //置顶专题加上关注的专题都不够$top个时获取官方大专题
        if (count($category_ids) != $top) {
            $official_category_ids = Category::where('is_official', 0)
                ->where('count', '>=', 0)
                ->where('status', '>', Category::STATUS_DRAFT)
                ->where('parent_id', 0) //0代表顶级分类
                ->whereNotIn('id', $category_ids)
                ->take($top - count($category_ids))
                ->pluck('id')->toArray();
            $category_ids = array_merge($category_ids, $official_category_ids);
        }
        $categories = Category::whereIn('id', $category_ids)->get();
    } else {
        //未登录，随机取官方专题
        $categories = Category::where('is_official', 0)
            ->where('count', '>=', 0)
            ->where('status', '>', Category::STATUS_DRAFT)
            ->where('parent_id', 0) //0代表顶级分类
            ->whereNotIn('id', $stick_categorie_ids)
            ->orderBy(DB::raw('RAND()'))
            ->take($top_count)
            ->get();
    }

    //首页推荐专题 合并置顶的专题
    $categories = get_top_categoires($categories);
    return $categories;
}

/**
 * 首页的文章列表
 * @return collection([article]) 包含分页信息和移动ＶＵＥ等优化的文章列表
 */
function indexArticles()
{
    $qb = Article::from('articles')
        ->exclude(['body', 'json'])
        ->where('status', '>', Article::STATUS_REVIEW)
        ->whereNull('source_url') //非采集文章..
        ->latest('updated_at');
    return smartPager($qb);
}

/**
 * 古老的返回无限多级分类的
 * @deprecated 没地方用了
 */
function get_categories($full = 0, $type = 'article', $for_parent = 0)
{
    $categories = [];
    if ($for_parent) {
        $categories[0] = null;
    }
    $category_items = Category::where('type', $type)->orderBy('order', 'desc')->get();
    foreach ($category_items as $item) {
        if ($item->level == 0) {
            $categories[$item->id] = $full ? $item : $item->name;
            if ($item->has_child) {
                foreach ($category_items as $item_sub) {
                    if ($item_sub->parent_id == $item->id) {
                        $categories[$item_sub->id] = $full ? $item_sub : ' -- ' . $item_sub->name;
                        foreach ($category_items as $item_subsub) {
                            if ($item_subsub->parent_id == $item_sub->id) {
                                $categories[$item_subsub->id] = $full ? $item_subsub : ' ---- ' . $item_subsub->name;
                            }
                        }
                    }
                }
            }
        }
    }
    $categories = collect($categories);
    return $categories;
}

/**
 * 古老的返回首页置顶轮播图文
 */
function get_carousel_items($category_id = 0)
{
    $carousel_items = [];
    if (isMobile()) {
        return $carousel_items;
    }
    $query = Article::orderBy('id', 'desc')
        ->where('image_top', '<>', '')
        ->where('is_top', 1);
    if ($category_id) {
        $query = $query->where('category_id', $category_id);
    }
    $top_pic_articles = $query->take(5)->get();
    $carousel_index   = 0;
    foreach ($top_pic_articles as $article) {
        $item = [
            'index'       => $carousel_index,
            'id'          => $article->id,
            'title'       => $article->title,
            'description' => $article->description,
            'image_url'   => $article->cover,
            'image_top'   => $article->image_top,
        ];
        $carousel_items[] = $item;
        $carousel_index++;
    }
    return $carousel_items;
}
