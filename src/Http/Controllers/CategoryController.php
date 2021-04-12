<?php

namespace Haxibiao\Content\Http\Controllers;

use App\Article;
use App\Category;
use App\User;
use Haxibiao\Content\Requests\CategoryRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['store', 'create', 'update', 'destroy', 'edit']]);
        $this->middleware('auth.admin', ['only' => ['list']]);
    }

    /**
    专题管理列表
     */
    function list(Request $request) {
        $qb               = Category::where('status', '>=', 0)->orderBy('id', 'desc');
        $data['keywords'] = '';
        if ($request->get('q')) {
            $keywords         = $request->get('q');
            $data['keywords'] = $keywords;

            //以下这段很糟糕的写法是因为 order by name asc 无法满足需求
            //如果使用sortBy() 来回调排序的话 无法使用paginate()
            //由于是编辑需求 故简单处理 后期优化
            //精准匹配
            $accurateCategory         = Category::where('status', '>=', 0)->where('name', $keywords)->orderBy('id', 'desc')->paginate(5);
            $data['accurateCategory'] = $accurateCategory;
            //模糊匹配
            $qb = Category::orderBy('name', 'asc')->where('status', '>=', 0)
                ->where('name', 'like', "%$keywords%")->where('name', '!=', $keywords);
        }
        $type = $request->get('type') ?: 'article';

        switch ($type) {
            case 'question':
                $qb = $qb->where('count_questions', '>', 0);
                break;
            case 'video':
                $qb = $qb->where('count_videos', '>', 0);
                break;
            case 'snippet':
                $qb = $qb->where('count_snippets', '>', 0);
                break;
            default:
                $qb = $qb->where('count', '>=', 0);
                break;
        }
        $categories         = $qb->paginate(12);
        $data['categories'] = $categories;
        return view('category.list')->withData($data);
    }

    /**
     *   专题首页
     */
    public function index(Request $request)
    {
        $qb   = Category::where('status', '>=', 0)->orderByDesc('count');
        $type = 'article';
        if ($request->get('type')) {
            $type = $request->get('type');
        }
        switch ($type) {
            case 'question':
                $qb = $qb->where('count_questions', '>', 0);
                break;

            default:
                $qb = $qb->where('count', '>=', 0);
                break;
        }

        //推荐
        $categories = $qb->where('status', 1)->where('parent_id', 0)->paginate(12);
        if (ajaxOrDebug() && request('recommend')) {
            foreach ($categories as $category) {
                $category->followed = $category->isFollowed();
                $category->count += $category->subCategory()->pluck('count')->sum();
            }
            return $categories;
        }
        $data['recommend'] = $categories;

        //热门
        //获取最近七天发布的Article 按照hits order by desc
        $week_start = Carbon::now()->subWeek()->startOfWeek()->toDateTimeString();

        $categories = Category::whereExists(function ($query) use ($week_start) {
            return $query->from('articles')
                ->whereRaw('categories.id = articles.category_id')
                ->where('articles.status', '>=', 0)
                ->where('updated_at', '<=', $week_start);
        })
            ->where('status', 1)
            ->where('parent_id', 0)
            ->paginate(24);
        if (ajaxOrDebug() && request('hot')) {
            foreach ($categories as $category) {
                $category->followed = $category->isFollowed();
                $category->count += $category->subCategory()->pluck('count')->sum();
            }
            return $categories;
        }
        $data['hot'] = $categories;

        //分类列表页面未使用到子分类数量字段暂时注释
        // //取子分类总和
        // foreach ($data as $categories) {
        //     foreach ($categories as $category) {
        //         $category->count += $category->subCategory()->pluck('count')->sum();
        //     }
        // }

        //TODO:: 后期根据地理位置获得城市，多关联一个城市的分类，方便用户看附近的内容
        //城市
        // $categories = $qb->paginate(24);
        // if (ajaxOrDebug() && request('city')) {
        //     foreach ($categories as $category) {
        //         $category->followed = $category->isFollowed();
        //     }
        //     return $categories;
        // }
        // $data['city'] = $categories;

        return view('category.index')
            ->withData($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        return view('category.create')->withUser($user);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        $category = new Category($request->except('uids', 'categories'));
        $category->save();
        //save logo
        $category->saveLogo($request);
        $category->save();
        //子分类
        if (request()->filled('categories')) {
            $categories   = json_decode($request->categories, true);
            $category_ids = array_column($categories, 'id');
            Category::whereIn('id', $category_ids)
                ->update(['parent_id' => $category->id]);
        }
        //save admins ...
        $this->saveAdmins($category, $request);
        return redirect()->to('/category');
    }

    //专题管理员维护
    public function saveAdmins($category, $request)
    {
        $admins = json_decode($request->uids, true);
        //防止重复选人
        $admin_ids = [];
        if (!empty($admins)) {
            $admin_ids = array_unique(array_pluck($admins, 'id'));
        }
        $auth_id = $request->user()->id;
        if (!in_array($auth_id, $admin_ids)) {
            array_push($admin_ids, $auth_id);
        }
        if (is_array($admin_ids)) {
            $data = [];
            foreach ($admin_ids as $id) {
                $data[$id] = ['is_admin' => 1];
            }
            $category->admins()->sync($data);
        }
        //创建者默认还是加成管理 ? 不需要了，$owner可以随时填充过去显示，无需冗余
    }

    public function name_en(Request $request, $name_en)
    {
        $category = Category::where('name_en', $name_en)->firstOrFail();
        return $this->showCate($request, $category);
    }

    public function show(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        return $this->showCate($request, $category);
    }

    public function showCate($request, Category $category)
    {
        $qb = $category->publishedWorks()
            ->with('user')->with('category');

        //最新评论
        $qb                = $qb->orderBy('commented', 'desc');
        $articles          = smartPager($qb, 10);
        $data['commented'] = $articles;
        if (ajaxOrDebug() && $request->get('commented')) {
            foreach ($articles as $article) {
                $article->fillForJs();
                $article->time_ago = $article->updatedAt();
            }
            return $articles;
        }

        //作品
        $qb            = $qb->orderBy('pivot_created_at', 'desc');
        $articles      = smartPager($qb, 10);
        $data['works'] = $articles;

        if (ajaxOrDebug() && $request->get('works')) {
            foreach ($articles as $article) {
                $article->fillForJs();
                $article->time_ago = $article->updatedAt();
            }
            return $articles;
        }

        //热门文章
        $qb          = $qb->orderBy('hits', 'desc');
        $articles    = smartPager($qb, 10);
        $data['hot'] = $articles;

        if (ajaxOrDebug() && $request->get('hot')) {
            foreach ($articles as $article) {
                $article->fillForJs();
                $article->time_ago = $article->updatedAt();
            }
            return $articles;
        }

        //相关专题,加入层级关系
        $level_categories = Category::where('id', '<>', $category->id)
            ->whereStatus(1)
            ->where('parent_id', $category->id)
            ->when($category->parent_id != 0, function ($q) use ($category) {
                return $q->orWhere('parent_id', $category->id);
            })->get();
        if (count($level_categories) == 0) {
            $user = User::find($category->user_id);
            if ($user) {
                $data['related_category'] = $user
                    ->adminCategories
                    ->take(5);
            }
        } else {
            $data['related_category'] = $level_categories;
        }

        //记录日志
        $category->recordBrowserHistory();

        $questions = [];
        if (enable_question()) {
            $questions = \App\Question::where('category_id', $category->id)->paginate(9);
        }
        return view('category.show')
            ->withCategory($category)
            ->with("questions", $questions)
            ->withData($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $type = 'article';
        if ($request->get('type')) {
            $type = $request->get('type');
        }
        $user     = Auth::user();
        $category = Category::with('user')->find($id);
        if (!canEdit($category)) {
            abort(403);
        }
        // dd(json_encode($category->admins->pluck('name','id')));
        //$categories = get_categories(0, $type, 1);
        $categories = Category::where('parent_id', $id)
            ->whereStatus(1)
            ->get();
        return view('category.edit')->withUser($user)
            ->withCategory($category)
            ->withCategories($categories);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        if (!canEdit($category)) {
            abort(403);
        }
        $category->update($request->except('uids', 'categories'));
        //save logo
        $category->saveLogo($request);
        $category->updated_at = now();
        $category->save();
        //维护子分类
        $old_category_ids = Category::where('parent_id', $id)
            ->whereStatus(1)
            ->pluck('id')
            ->toArray();
        if (request()->filled('categories')) {
            $categories          = json_decode($request->categories, true);
            $recent_category_ids = array_column($categories, 'id');
        } else {
            $recent_category_ids = [];
        }
        $exclude_c_ids = array_udiff_assoc($old_category_ids, $recent_category_ids, function ($a, $b) {
            $b = intval($b);
            if ($a === $b) {
                return 0;
            }
            return ($a > $b) ? 1 : -1;
        });
        if (!empty($exclude_c_ids)) {
            Category::whereIn('id', $exclude_c_ids)
                ->update(['parent_id' => 0]);
        }
        if (!empty($recent_category_ids)) {
            Category::whereIn('id', $recent_category_ids)
                ->update(['parent_id' => $category->id]);
        }

        //save admins ...
        $this->saveAdmins($category, $request);
        return redirect()->to('/category');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        if (!canEdit($category)) {
            abort(403);
        }
        if ($category) {
            $count = \App\Article::where('category_id', $category->id)->where('status', '>', 0)->count();
            if ($count == 0) {
                if (Category::where('parent_id', $id)->count()) {
                    return '该分类下还有分类，不能删除';
                }
                $category->status = -1;
                $category->save();
            } else {
                return '该分类下还有文章，不能删除';
            }
        }
        return redirect()->back();
    }
}
