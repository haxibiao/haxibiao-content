<?php

namespace Haxibiao\Content\Http\Api;

use App\Article;
use App\Category;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Notifications\ArticleApproved;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function search(Request $request, $aid)
    {
        $article    = Article::findOrFail($aid);
        $query      = $request->get('q');
        $categories = Category::where('name', 'like', '%' . $query . '%')
            ->paginate(12);
        foreach ($categories as $category) {
            $cate                      = $article->categories()->where('categories.id', $category->id)->first();
            $category->submited_status = "";
            if ($cate) {
                $category->submited_status = $cate->pivot->submit;
            }
            $category->fillForJs();
            $category->submit_status = get_submit_status($category->submited_status);
        }
        return $categories;
    }

    public function newLogo(Request $request)
    {
        $category = new Category();
        $category->saveLogo($request);
        return $category->logoUrl;
    }
    public function editLogo(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->saveLogo($request);
        $category->save();
        return $category->logoUrl;
    }

    public function index(Request $request)
    {
        //网页VUE 发布动态，选择专题用，只取最近top100专题 直接投稿用
        $categories = Category::latest('updated_at')
            ->whereStatus(1)
            ->take(100)
            ->get();
        foreach ($categories as $category) {
            $category->fillForJs();
        }
        return $categories;
    }

    public function page(Request $request)
    {
        $categories = null;
        if ($request->get('index')) {
            $stick_categories = get_stick_categories();
            $top_count        = 7 - count($stick_categories);
            $categories       = Category::where('is_official', '0')
                ->where('status', '>=', 0)
                ->where('count', '>=', 0)
                ->orderBy('updated_at', 'desc')
                ->take($top_count)
                ->get();
        } else {
            $categories = Category::orderBy('updated_at', 'desc')
                ->where('is_official', '0')
                ->where('status', '1')
                ->paginate(7);
        }
        foreach ($categories as $category) {
            $category->fillForJs();
        }
        return $categories;
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        $category->fillForJs();
        return $category;
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->all());
        $category->fillForJs();
        return $category;
    }

    //投稿请求的逻辑放这里了
    public function newReuqestCategories(Request $request)
    {
        $user = $request->user();
        //获取我所有被投过稿的专题
        $category = Category::where('user_id', $user->id)->whereNotNull('new_request_title')->get();
        return $category;
    }

    public function pendingArticles(Request $request)
    {
        $user = $request->user();
        //FIXME: 这里应该UseCategory里的关系
        // $categorizable_ids = Categorizable::where('submit', '待审核')->pluck('categorizable_id');
        // foreach ($categorizable_ids as $categorizable_id) {
        //     return Article::where('id', $categorizable_id)->with('user')->get();
        // }
        return collect([]);
    }

    public function requestedArticles(Request $request, $cid)
    {
        $category = Category::findOrFail($cid);
        return $category->requestedInMonthArticles->load('user');
    }

    public function checkCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $user     = $request->user() ?: Auth::guard('api')->user();
        $isAdmin  = $category->admins->contains($user);
        $query    = $user->articles();
        if (request('q')) {
            $query = $query->where('title', 'like', '%' . request('q') . '%');
        }
        $articles = $query->paginate(10);
        foreach ($articles as $article) {
            $article->submited_status = '';
            $query                    = $article->allCategories()->wherePivot('category_id', $category->id);
            if ($query->count()) {
                $article->submited_status = $query->first()->pivot->submit;
            }
            //如果是专题的管理
            if ($isAdmin) {
                $article->submit_status = $article->submited_status == '已收录' ? '移除' : '收录';
            } else {
                $article->submit_status = get_submit_status($article->submited_status);
            }
        }

        return $articles;
    }

    public function submitCategory(Request $request, $aid, $cid)
    {
        $user     = $request->user();
        $article  = Article::findOrFail($aid);
        $category = Category::findOrFail($cid);

        //将文章投稿进专题
        $query = $article->allCategories()->wherePivot('category_id', $cid);

        //已经投过稿
        if ($query->count()) {
            $pivot         = $query->first()->pivot;
            $pivot->submit = $pivot->submit == '待审核' ? '已撤回' : '待审核';
            $pivot->save();
            $article->submited_status = $pivot->submit;

            //清除缓存
            foreach ($category->admins as $admin) {
                $admin->forgetUnreads();
            }
            $category->user->forgetUnreads();
            //撤销投稿后，分类下新的投稿数需要重新计算
            $category->new_requests = $category->articles()->wherePivot('submit', '待审核')->count();
            $category->save();
        } else {
            $article->submited_status = '待审核';
            $article->allCategories()->syncWithoutDetaching([
                $cid => [
                    'submit' => $article->submited_status,
                ],
            ]);
        }

        //给所有管理员延时10分钟发通知，提示有新的投稿请求
        if ($article->submited_status == '待审核') {
            // SendCategoryRequest::dispatch($article, $category)->delay(now()->addMinutes(1));

            //给所有专题管理发通知
            foreach ($category->admins as $admin) {
                $admin->forgetUnreads();
            }
            //also send to creator
            $category->user->forgetUnreads();

            //TODO::如果后面撤回了，这个标题也留这了
            $category->new_request_title = $article->title;

            //更新单个专题上的新请求数
            $category->new_requests = $category->requestedInMonthArticles()->wherePivot('submit', '待审核')->count();
            $category->save();
        }

        $article->submit_status = get_submit_status($article->submited_status);
        return $article;
    }

    /**
     * 专题收录
     */
    public function addCategory(Request $request, $aid, $cid)
    {
        $user     = $request->user();
        $article  = Article::findOrFail($aid);
        $category = Category::findOrFail($cid);

        $query = $category->articles()->wherePivot('categorizable_id', $aid);
        if ($query->count()) {
            $pivot         = $query->first()->pivot;
            $pivot->submit = $pivot->submit == '已收录' ? '已移除' : '已收录';
            $pivot->save();
            $submited_status = $pivot->submit;
        } else {
            $category->articles()->syncWithoutDetaching([
                $aid => [
                    'submit' => '已收录',
                ],
            ]);
            $submited_status = '已收录';
        }

        if ($submited_status == '已收录') {
            if ($user->id != $article->user->id) {
                //通知文章作者文章被收录
                $article->user->notify(new ArticleApproved($article, $category, $submited_status));
                $article->user->forgetUnreads();
            }
            //被收录文章的主专题标签更新
            $article->category_id = $cid;
            $article->save();
        } else {
            //移除收录的文章，不再通知，但是应该更新文章的主专题为最后一次的主专题
            $lastCategory = $article->allCategories()->orderBy('pivot_updated_at', 'desc')->first();
            if ($lastCategory) {
                //被收录文章的主专题标签更新
                $article->category_id = $lastCategory->id;
                $article->save();
            }
        }

        //更新专题文章数
        $category->count = $category->publishedArticles()->count();
        $category->save();

        //返回给ui
        $category->submit_status   = $submited_status == '已收录' ? '移除' : '收录';
        $category->submited_status = $submited_status;
        return $category;
    }

    public function adminCategoriesCheckArticle(Request $request, $aid)
    {
        $article                  = Article::findOrFail($aid);
        $user                     = $request->user() ?: Auth::guard('api')->user();
        throw_if(!$user,GQLException::class,'用户不存在，请换一个哦！！');
        $qb                       = $user->adminCategories()->with('user');
        $data['accurateCategory'] = $user->adminCategories()->with('user')->where('categories.name', request('q'))->paginate(5);
        if (request('q')) {
            $keyWords = request('q');
            $qb       = $qb->where('categories.name', 'like', "%$keyWords%")->where('categories.name', '!=', "$keyWords");
        }
        $data['categories'] = $qb->paginate(12);
        //获取当前文章的投稿状态
        foreach ($data as $item) {
            foreach ($item as $category) {
                $category->submited_status = '';
                $cateWithPivot             = $article->categories()->wherePivot('category_id', $category->id)->first();
                if ($cateWithPivot) {
                    $category->submited_status = $cateWithPivot->pivot->submit;
                }
                $category->submit_status = $category->submited_status == '已收录' ? '移除' : '收录';
                $category->fillForJs();
            }
        }
        return $data;
    }

    public function recommendCategoriesCheckArticle(Request $request, $aid)
    {
        $article = Article::findOrFail($aid);
        $user    = $request->user() ?: Auth::guard('api')->user();
        $qb      = Category::orderBy('id', 'desc')
            ->whereNotIn('id', $user->adminCategories()->pluck('categories.id'));
        $data['accurateCategory'] = Category::orderBy('id', 'desc')
            ->whereNotIn('id', $user->adminCategories()->pluck('categories.id'))->where('categories.name', request('q'))->paginate(5);
        if (request('q')) {
            $keyWords = request('q');
            $qb       = $qb->where('categories.name', 'like', "%$keyWords%")->where('categories.name', '!=', "$keyWords");
        }
        $data['categories'] = $qb->paginate(12);
        foreach ($data as $item) {
            $item->map(function ($category) use ($article) {
                $category->submited_status = '';
                $cateWithPivot             = $article->categories()->wherePivot('category_id', $category->id)->first();
                if ($cateWithPivot) {
                    $category->submited_status = $cateWithPivot->pivot->submit;
                }
                $category->submit_status = $category->submited_status == '已收录' ? '移除' : '投稿';
                $category->fillForJs();
                return $category;
            });
        }
        return $data;
    }

    //审核投稿
    public function approveCategory(Request $request, $cid, $aid)
    {
        $category = Category::findOrFail($cid);

        $article = $category->articles()->where('categorizable_id', $aid)->firstOrFail();

        //清除缓存
        foreach ($category->admins as $admin) {
            $admin->forgetUnreads();
        }
        $user = $request->user();
        $user->forgetUnreads();

        //更新投稿请求的状态
        $pivot         = $article->pivot;
        $pivot->submit = $request->get('deny') ? '已拒绝' : '已收录';
        if ($request->get('remove')) {
            $pivot->submit = '已移除';
        }
        $pivot->save();

        if ($pivot->submit == '已收录') {
            //接受文章，更新专题文章数
            $category->count = $category->publishedArticles()->count();
            //更新文章主分类,方便上首页
            $article->category_id = $cid;
            $article->save();

            //自动置顶最新收录的文章到发现，时间由pm来规定 没有就1天
            $stick_articles = collect(get_stick_articles());
            $is_stick       = $stick_articles->search(function ($item, $key) {
                if ($item->id == 1374) {
                    return true;
                }
            });
            if ($article->status > 0 && $is_stick) {
                $expire = 1; //TODO: 新文章默认自动置顶1天
                stick_article([
                    'article_id' => $article->id,
                    'expire'     => $expire,
                    'position'   => '发现',
                    'reason'     => '新收录',
                ], true);
            }

            //一旦收录成功一篇文章，该用户自动成为本专题作者, pivot.approved = 该专题收录他的文章数
            $cate = $article->user->categories()->find($category->id);
            if (!$cate) {
                $article->user->categories()->syncWithoutDetaching([
                    $category->id => ['count_approved' => 1],
                ]);
            } else {
                //更新作者在专题的收录数
                $cate->pivot->count_approved = $cate->pivot->count_approved + 1;
                $cate->pivot->save();
            }
        }

        //重新统计专题上的未处理投稿数...
        $category->new_requests = $category->requestedInMonthArticles()->wherePivot('submit', '待审核')->count();
        $category->save();

        //发送通知给投稿者
        $article->user->notify(new ArticleApproved($article, $category, $pivot->submit));
        $article->user->forgetUnreads();

        //收录状态返回给UI
        // $article->submit_status   = get_submit_status($submited_status);
        // $article->submited_status = $submited_status;
        $article->load('user');

        return $article;
    }

    /**
     * @Author      XXM
     * @DateTime    2018-11-03
     * @description [返回当前专题下相关视频]
     * @param       [type]        $category_id
     * @return      [Json]                     [Videos]
     */
    public function getCategoryVideos($category_id)
    {
        $video_id = request()->get('video_id');
        $num      = request()->get('num') ? request()->get('num') : 10;

        $posts = [];
        if ($category = Category::findOrFail($category_id)) {
            $posts = $category->posts()
                ->with('video')
                ->where('video_id', '!=', $video_id)
                ->paginate($num);
            foreach ($posts as $article) {
                $article->fillForJs();
            }
        }

        return $posts;
    }
}
