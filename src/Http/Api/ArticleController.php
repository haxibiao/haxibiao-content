<?php

namespace Haxibiao\Content\Http\Api;

use App\Http\Controllers\Controller;
use Haxibiao\Content\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
        //FIXME: 还没为admin editor的逻辑处理，目前简单登录即可管理任何文章
    }

    //首页文章列表的api
    public function index()
    {
        $articles = cmsTopArticles();
        //下面是为了兼容VUE
        foreach ($articles as $article) {
            $article->fillForJs();
            $article->time_ago = $article->updatedAt();
        }
        return $articles;
    }

    public function trash(Request $request)
    {
        $user     = $request->user();
        $articles = $user->removedArticles;
        return $articles;
    }

    public function store(Request $request)
    {
        $article          = new Article($request->all());

        // 格式化description
		$body    	 = $request->input('body');
		$description = $request->input('description');
		if(!$description){
			$description = str_purify($body);
			$description = Str::limit($description, 100);
		}
		$article->description 	= $description;

        $article->user_id = $request->user()->id;
        $article->save();

        //images
        $article->saveRelatedImagesFromBody();

		// 处理封面图
		$article->cover_path =  data_get($article,'images.0.url');
		$article->save();
		$article->fresh();
        return $article;
    }

    public function update(Request $request, $id)
    {
        $article              = Article::findOrFail($id);
        $article->count_words = ceil(strlen(strip_tags($article->body)) / 2);
        $article->update($request->all());

        //images
        $article->saveRelatedImagesFromBody();

        // 格式化description
		$body    	 = $request->input('body');
		$description = $request->input('description');
		if(!$description){
			$description = str_purify($body);
			$description = Str::limit($description, 100);
		}
		$article->description 	= $description;

		// 处理封面图
		$article->cover_path =  data_get($article,'images.0.url');

        $article->save();
		$article->fresh();

        return $article;
    }

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();
        try
        {
            //FIXME: 监听的 Observer 里处理
            //彻底删除文章，删除相关数据

            $result = Article::destroy($id);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return 0;
        }
    }

    public function delete(Request $request, $id)
    {
        $article         = Article::findOrFail($id);
        $article->status = -1;
        $article->save();
        return $article;
    }

    public function restore(Request $request, $id)
    {
        $article         = Article::findOrFail($id);
        $article->status = 0;
        $article->save();
        //如果文集也被删除了，恢复出来
        if ($article->collection->status == -1) {
            $article->collection->status = 0;
            $article->collection->save();
        }

        return $article;
    }

    public function likes(Request $request, $id)
    {
        $article = Article::findOrFail($id);
        $likes   = $article->likes()->with('user')->paginate(10);
        foreach ($likes as $like) {
            $like->created_at = $like->createdAt();
        }
        return $likes;
    }

    public function show($id)
    {
        $article = Article::with('user')->with('category')->with('images')->findOrFail($id);
        $article->fillForJs();
        if (!empty($article->category_id)) {
            $article->category->fillForJs();
        }
        $article->pubtime = diffForHumansCN($article->created_at);
        return $article;
    }

    public function resolverDouyinVideo(Request $request)
    {
        if (isset($request->all()['params']['share_link'])) {
            $data = $request->all()['params'];
        } else {
            return response('请检查输入的信息是否正确完整噢');
        }

        // 效验登录
        $user_id = $data['user_id'];
        if (empty($user_id) || is_null($user_id)) {
            return response('当前未登录，请登录后再尝试哦');
        }

        // 登录
        Auth::loginUsingId($user_id);

        // 过滤文本，留下 url
        $link = $data['share_link'];
        $link = filterText($link)[0];

        // 不允许重复视频
        if (Article::where('source_url', $link)->exists()) {
            return response('视频已经存在，请换一个视频噢');
        }

        // 爬取关键信息
        $spider = app('DouyinSpider');
        $data   = json_decode($spider->parse($link), true);

        // 去除 “抖音” 关键字, TODO :做一个大些的关键词库，封装重复操作
        $data['0']['desc'] = str_replace('@抖音小助手', '', $data['0']['desc']);
        $data['0']['desc'] = str_replace('抖音', '', $data['0']['desc']);

        // 保存并 更新原链接
        $article = new Article();
        $article = $article->parseDouyinLink($data);
        $article->update([
            'source_url' => $link,
        ]);

        return $article;
    }
}
