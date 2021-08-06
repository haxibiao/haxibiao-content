<?php

namespace Haxibiao\Content\Traits;

use App\User;
use Haxibiao\Breeze\Dimension;
use Haxibiao\Content\Category;
use Haxibiao\Media\SearchLog;
use Haxibiao\Question\CategoryUser;
use Haxibiao\Question\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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

    public function saveIcon($icon)
    {
        if (is_object($icon)) {
            $imageType = $icon->getClientOriginalExtension();
            $realPath  = $icon->getRealPath(); //临时文件的绝对路径

            $path       = 'categories/' . $this->id . ".{$imageType}";
            $imageMaker = Image::make($realPath);
            $iconWidth  = 200;
            $iconHeight = 200;
            $imageMaker->resize($iconWidth, $iconHeight);
            $imageMaker->save($realPath);

            //上传到Cos
            $cosPath = 'storage/app' . env('APP_NAME') . '/' . $path;
            Storage::cloud()->put($cosPath, \file_get_contents($realPath));

            //更新头像路径
            $this->icon = cdnurl($cosPath);
            $this->save();
        }
        return $this;
    }

    public function syncAnswersCount()
    {
        $this->answers_count = Question::where('category_id', $this->id)
            ->selectRaw('sum(correct_count) + sum(wrong_count) as answers_count')
            ->first()
            ->answers_count;
        return $this;
    }

    public function updateRanks()
    {
        $qb = Question::select('rank')
            ->where('submit', '>=', Question::REVIEW_SUBMIT) //包含待审核的，因为可以刷到待审题
            ->where('category_id', $this->id)
            ->groupBy('rank');
        $ranks = $qb->pluck('rank')->toArray();
        rsort($ranks);
        $this->ranks = $ranks;
        $this->save();
    }

    public function isDisallowSubmit()
    {
        return $this->allow_submit == Category::DISALLOW_SUBMIT;
    }

    /**
     * ===========================
     * question包的reop方法
     * ===========================
     */
    //最新上线题库
    public static function newestCategories($offset, $limit, $notInIds = [])
    {
        $qb = Category::query()->latest('id')->published()->take($limit)->skip($offset);

        if (count($notInIds)) {
            $qb->whereNotIn('id', $notInIds);
        }

        if ($qb->count('id') < $limit) {
            return Category::query()->published()->inRandomOrder()->take($limit)->get();
        }
        return $qb->get();
    }

    //猜你喜欢
    public static function guestUserLike($offset, $limit)
    {
        $user = getUser(false);
        $qb   = Category::query()->published()
            ->whereIn('type', [Category::ARTICLE_TYPE_ENUM, Category::QUESTION_TYPE_ENUM])
            ->latest('rank');
        $categories = $qb->take($limit)->skip($offset)->get();
        if ($user) {
            //获取登录用户最近答题的3个题库
            $visitedCates = $user->recentVisitCategories(3);
            $categories   = $visitedCates->merge($categories);
            $categories   = $categories->unique('id'); //排重
        }
        return $categories;
    }

    public static function recommendCategories($offset, $limit)
    {
        $month = now()->format('Y-m');
        $qb    = Category::query()->published()
            ->whereIn('type', [Category::ARTICLE_TYPE_ENUM, Category::QUESTION_TYPE_ENUM])
            ->orderBy('rank', 'desc')
            ->orderBy('answers_count_by_month->' . $month, 'desc')
            ->take($limit)
            ->skip($offset);
        app_track_event("答题", "随机题库");
        // 题库数量不够了, 随机给题库
        if ($qb->count('id') < $limit) {
            return Category::query()->published()->inRandomOrder()->take($limit)->get();
        }

        return $qb->get();
    }

    public static function getCategories($args, $offset, $limit)
    {
        $allowSubmit = Arr::get($args, 'allow_submit');
        $keyword     = Arr::get($args, 'keyword');

        /**
         * 搜索 关键词
         * allowSubmit -1:置顶前5个最近的分类,其余正常排序
         * allowSubmit 0:用户能出题的分类
         * allowSubmit 1:允许出题的分类
         * allowSubmit 2:展示所有正常分类
         */

        //默认权重排序
        $qb = Category::published()
            ->whereIn('type', [Category::ARTICLE_TYPE_ENUM, Category::QUESTION_TYPE_ENUM])
            ->latest('rank');
        $qb = $allowSubmit >= 0 ? $qb->allowSubmit() : $qb->skipParent();

        //搜索
        if (!empty($keyword)) {
            $qb = $qb->ofKeyword($keyword);
        }

        $user = currentUser();
        //用户能答题的分类 按照最近时间排序
        if ($allowSubmit == -1 && !is_null($user)) {
            $latestCategories = collect();
            if ($offset = 0) {
                $latestCategories = $user->recentVisitCategories();
                $limit            = $limit - $latestCategories->count();
            }

            //少于5个
            if ($limit > 0) {
                $categories = $qb->whereNotIn('id', $latestCategories->pluck('id'))->take($limit)->skip($offset)->get();
                $categories = $latestCategories->merge($categories);
            }

            return $categories;
        }

        //分类列表
        $categories = $qb->skip($offset)->take($limit)->get();

        //获取用户能出题的分类
        if ($allowSubmit == 0 && !is_null($user)) {
            /**
             * 后端做起来应用交互体验较差,这一块放开给前端处理.
             */
        }

        //允许出题的分类
        if ($allowSubmit == 1) {

            //用户存在
            if (!is_null($user)) {
                $categoryUsers = CategoryUser::select(['category_id', 'correct_count'])->where('user_id', $user->id)->get();

                //用户是否可出题
                foreach ($categories as $category) {
                    $categoryUser              = $categoryUsers->firstWhere('category_id', $category->id);
                    $category->user_can_submit = !is_null($categoryUser) && $categoryUser->correct_count >= $category->min_answer_correct;
                }
            }
            $sortCategories = $categories->sortByDesc(function ($category) {
                if ($category->user_can_submit == true) {
                    return $category->answers_count;
                }
                return 0;
            });

            return $sortCategories;
        }

        return $categories;
    }

    public static function getCategoriesCanSubmit()
    {

        $user = getUser();

        //用户能答对10个的分类（用户可出题） 最近用户答过靠前
        // $qb = $user->canSubmitCategories()
        //     ->latest('pivot_updated_at');
        // ->where('correct_count', '>', 10);
        $categoryTable     = (new Category)->getTable();
        $categoryUserTable = (new CategoryUser)->getTable();

        //官方允许出题的
        $qb = Category::select("${categoryTable}.*")
            ->whereNotIn("${categoryTable}.id", [Category::RECOMMEND_VIDEO_QUESTION_CATEGORY])
            ->leftJoin("${categoryUserTable}", function ($join) use ($user, $categoryTable, $categoryUserTable) {
                $join->on("${categoryTable}.id", "${categoryUserTable}.category_id")
                    ->on("${categoryUserTable}.user_id", DB::raw($user->id));
            })->publishedQuestionTypeAndAllowSubmit()->latest("${categoryUserTable}.correct_count");

        return $qb;
    }

    public static function getLatestVisitCategories(User $user, $count = 5)
    {
        if ($action = $user->action) {
            //获取用户行为数据中最近浏览的五个分类
            return $action->getLatestCategories($count);
        }
        return collect([]);
    }

    //题库列表（或可出题的）
    public static function getAllowCategories($allow_submit = 0)
    {
        //默认rank权重排序
        $qb = Category::latest('rank');
        if ($allow_submit) {
            //FIXME: 过滤掉自己不能出题的分类
        }
        return $qb;
    }

    public function incrementCountAnswerByMouth()
    {
        $month = now()->format('Y-m');
        if (empty($this->answers_count_by_month)) {
            $this->answers_count_by_month = [$month => 1];
        } else {
            $value                        = Arr::get($this->answers_count_by_month, $month, null);
            $this->answers_count_by_month = [$month => empty($value) ? 1 : $value + 1];
        }
        $this->save();
    }

    //搜索题库
    public static function searchCategories($user, $keyword)
    {
        //默认rank权重排序
        $qb = Category::latest('rank')
            ->whereIn('type', [Category::ARTICLE_TYPE_ENUM, Category::QUESTION_TYPE_ENUM]);

        //搜索
        if (!empty($keyword)) {
            Dimension::track("题库搜索数", 1, "搜索");
            $qb = $qb->ofKeyword($keyword);
        }

        SearchLog::saveSearchLog($keyword, $user->id, "categories");

        if ($qb->count() > 0) {
            Dimension::track("题库搜索成功数", 1, "搜索");
        }

        return $qb;
    }

    //生成每日答题数的cache key
    public static function makeDailyAnswersCountCacheKey($date)
    {
        return sprintf('date:%s:category:answers:count', $date->format('Ymd'));
    }

    // 更新每日答题数缓存
    public function updateDailyAnswersCountCache()
    {
        $date         = today();
        $cacheKey     = Category::makeDailyAnswersCountCacheKey($date);
        $categoryId   = $this->id;
        $categoryName = $this->name;
        $cacheData    = Cache::get($cacheKey) ?? [];
        $isExisted    = false;
        foreach ($cacheData as &$item) {
            if ($item['category_id'] == $categoryId) {
                $item['name'] = $categoryName;
                $item['answers_count']++;
                $isExisted = true;
                break;
            }
        }
        //不存在就生成一条新的记录 push到数组中
        if (!$isExisted) {
            $cacheData[] = ['category_id' => $categoryId, 'answers_count' => 1, 'name' => $categoryName];
        }

        //第一天统计,第二天展示,第三天过期
        Cache::put($cacheKey, $cacheData, $date->addDay(2));
    }

    public static function getOrders()
    {
        return [
            '正序' => 'asc',
            '倒叙' => 'desc',
        ];
    }

    public static function getTopAnswersCategory($number = 5)
    {
        $data       = [];
        $categories = Category::orderByDesc('answers_count')
            ->select(['name', 'answers_count'])
            ->take($number)
            ->get()
            ->toArray();
        foreach ($categories as $category) {
            $data['value'][]   = $category['answers_count'];
            $data['options'][] = $category['name'];
        }
        return $data;
    }

    public static function getYesterdayTopAnswersCategory($maxNumber = 5)
    {
        // 从缓存中获取前一天的分类答题数
        $count     = 0;
        $data      = [];
        $cacheKey  = Category::makeDailyAnswersCountCacheKey(today()->subDay(1));
        $cacheData = Cache::get($cacheKey) ?? [];

        foreach ($cacheData as $category) {
            if ($count > $maxNumber) {
                break;
            }
            $data['value'][]   = $category['answers_count'];
            $data['options'][] = $category['name'];
            $count++;
        }
        return $data;
    }

    public function hasReviewQuestions()
    {
        return count($this->ranks) && max($this->ranks) == \App\Question::REVIEW_RANK;
    }

    public function userCanSubmit($user): bool
    {
        $categoryUsers = $user->categoriesPivot;
        $category      = $categoryUsers->where('category_id', $this->id)
            ->firstWhere('correct_count', '>=', $this->min_answer_correct);
        return !is_null($category);
    }

    public function userCanAudit($user): bool
    {
        $categoryUsers = $user->categoriesPivot;
        $category      = $categoryUsers->where('category_id', $this->id)
            ->firstWhere('can_audit', true);
        return !is_null($category);
    }

    public function answerCount($user)
    {
        $categoryUsers = $user->categoriesPivot;
        $category      = $categoryUsers->where('category_id', $this->id)->first();
        if ($category) {
            return $category->answer_count;
        }
        return 0;
    }

    public function reviewMustCorrectCount()
    {
        return $this->questions_count >= 100 ? 10 : 5;
    }

    public static function getAllowSubmits()
    {
        return [
            Category::ALLOW_SUBMIT    => '允许所有用户出题',
            Category::AUTO_SUBMIT     => '自动允许资深用户出题(未实现)',
            Category::DISALLOW_SUBMIT => '禁止所有用户出题',
        ];
    }

    public static function getStatuses()
    {
        return [
            Category::PUBLISH => '公开',
            Category::PRIVACY => '下架',
            Category::DELETED => '删除',
        ];
    }

    public static function getTypes()
    {
        return [
            Category::ARTICLE_TYPE_ENUM        => '文章',
            Category::QUESTION_TYPE_ENUM       => '题目',
            Category::FORK_QUESTION_TYPE_ENUM  => '分支题',
            Category::SCORE_QUESTION_TYPE_ENUM => '分数题',
        ];
    }

    public function getHashIdAttribute()
    {
        return \Hashids::encode($this->attributes['id']);
    }

    public function link()
    {
        switch ($this->type) {
            case Category::ARTICLE_TYPE_ENUM:
                $link = route('zixun.show', $this->id);
                break;
            default:
                $link = route('category', $this->id);
                break;
        }
        return $link;
    }

    public static function smartFindOrFail($id, $columns = ['*'])
    {
        $id = !is_numeric($id) ? \Hashids::decode($id)[0] ?? '' : $id;
        return !empty($id) ? parent::findOrFail($id, $columns) : null;
    }

    public function exerciseLink()
    {
        return route('category.exercise', ['code' => $this->id]);
    }

    public function syncChildrenCount()
    {
        return $this->children_count = $this->children()->count();
    }
}
