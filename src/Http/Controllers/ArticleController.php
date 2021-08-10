<?php

namespace Haxibiao\Content\Http\Controllers;

use App\Jobs\DelayArticle;
use App\Post;
use App\Tag;
use Haxibiao\Content\Article;
use Haxibiao\Content\Requests\ArticleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('show', 'shareVideo');
    }

    public function storePost(Request $request)
    {
        $article = new Article();
        $article->createPost($request->all());
        $article->saveCategories($request->get('categories'));
        return redirect()->to($article->url);
    }

    public function drafts(Request $request)
    {
        $query = Article::orderBy('id', 'desc')
            ->where('status', Article::STATUS_REVIEW)
            ->whereType('article');
        if (!Auth::user()->is_admin) {
            $query = $query->where('user_id', Auth::user()->id);
        }
        $articles = $query->paginate(10);
        return view('article.drafts')->withArticles($articles);
    }

    public function index(Request $request)
    {
        $query = Article::orderBy('id', 'desc')->where('status', '>', Article::STATUS_REVIEW)->whereType('article');
        //Search Articles
        $data['keywords'] = '';
        if ($request->get('q')) {
            $keywords         = $request->get('q');
            $data['keywords'] = $keywords;
            $query            = Article::orderBy('id', 'desc')
                ->where('status', '>', Article::STATUS_REVIEW)
                ->whereType('article')
                ->where('title', 'like', "%$keywords%");
        }
        if (!Auth::user()->is_admin) {
            $query = $query->where('user_id', Auth::user()->id);
        }
        $articles         = $query->paginate(10);
        $data['articles'] = $articles;
        return view('article.index')->withData($data);
    }

    public function create()
    {
        $categories = get_categories();
        return view('article.create')->withCategories($categories);
    }

    public function store(ArticleRequest $request)
    {
        // 校验
        $user = $request->user();
        if ($slug = $request->slug) {
            $validator = Validator::make(
                $request->input(),
                ['slug' => 'unique:articles']
            );
            if ($validator->fails()) {
                dd('当前slug已被使用');
            }
            if (is_numeric($slug)) {
                dd('slug 不能为纯数字');
            }
        }

        $article = new Article($request->all());

        // description
        $description = $request->input('description');
        if (!$description) {
            $description = str_purify($request->input('body'));
            $description = Str::limit($description, 100);
        }
        $article->description = $description;
        $article->save();

        //categories
        $article->saveCategories(request('categories'));

        //tags
        $this->save_article_tags($article);

        return redirect()->to('/article/' . $article->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!is_numeric($id)) {
            if ($id == 'question') {
                return view('disclaimer');
            }
        }
        //此处id为中文代表slug,且$id不会是create.
        $article = Article::where(function ($query) use ($id) {
            is_numeric($id) ? $query->whereId($id) : $query->whereSlug($id);
        })->firstOrFail();
        $article->load(['user.profile']);

        if ($article->status < Article::STATUS_ONLINE) {
            if (!canEdit($article)) {
                return abort(404);
            }
        }

        if ($article->category && $article->category->parent_id) {
            $data['parent_category'] = $article->category->parent()->first();
        }

        //记录用户浏览记录
        $article->recordBrowserHistory();

        //修复正文中写死的图片url为cdn版本 image->url属性的
        $article->body = $article->parsedBody();

        $data['recommended'] = Article::whereIn('category_id', $article->categories->pluck('id'))
            ->where('id', '<>', $article->id)
            ->where('status', Article::STATUS_ONLINE)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return view('article.show')
            ->withArticle($article)
            ->withData($data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //不是编辑或者admin无法使用编辑面板
        if (!checkEditor()) {
            abort(404);
        }
        $article = Article::with('images')->findOrFail($id);
        $article->load('images');

        $categories    = request()->user()->adminCategories;
        $article->body = str_replace('<single-list id', '<single-list class="box-related-half" id', $article->body);
        return view('article.edit')->withArticle($article)->withCategories($categories);
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
        $article = Article::findOrFail($id);

        if ($slug = $request->slug) {
            $validator = Validator::make(
                $request->input(),
                ['slug' => 'unique:articles,slug,' . $article->id]//校验时忽略当前文章
            );
            if ($validator->fails()) {
                dd('当前slug已被使用');
            }
            if (is_numeric($slug)) {
                dd('slug 不能为纯数字');
            }
        }

        $article->update([
            "title" => $request->title,
            "body"  => $request->body,
        ]);
        $article->edited_at   = \Carbon\Carbon::now();
        $article->count_words = ceil(strlen(strip_tags($article->body)) / 2);
        $article->source_url  = null; //手动编辑过的文章，都不再是爬虫文章
        $article->save();

        // description
        $description = $request->input('description');
        if (!$description || data_get($article, 'cover_path', null)) {
            $description = str_purify($request->input('body'));
            $description = Str::limit($description, 100);
        }
        $article->description = $description;
        $article->save();

        // cover
        $article->cover_path = data_get($article, 'images.0.url');
        $article->save();

        //编辑保存文章 改变动态？ XXM这个操作有点复杂，暂时不启用
        // $article->changeAction();

        //允许编辑时定时发布
        $this->process_delay($article);

        //categories
        $article->saveCategories(request('categories'));

        //tags
        $this->save_article_tags($article);

        if (!empty($article->slug)) {
            return redirect()->to('/article/' . $article->slug);
        }
        return redirect()->to('/article/' . $article->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        if (request('restore')) {
            $article->update(['status' => Article::STATUS_ONLINE]);
        } else {
            $article->update(['status' => Article::STATUS_REFUSED]);
        }
        return redirect()->back();
    }

    public function process_delay($article)
    {
        if (request()->delay) {
            $article->user_id    = Auth::id();
            $article->status     = Article::STATUS_REVIEW; //草稿
            $article->delay_time = now()->addDays(request()->delay);
            $article->save();

            DelayArticle::dispatch($article)
                ->delay(now()->addDays(request()->delay));
        }
    }

    public function save_article_tags($article)
    {
        $tag_ids  = [];
        $keywords = preg_split("/(#|:|,|，|\s)/", $article->keywords);
        foreach ($keywords as $word) {
            $word = trim($word);
            if (!empty($word)) {
                $tag = Tag::firstOrNew([
                    'name' => $word,
                ]);
                $tag->user_id = Auth::user()->id;
                $tag->save();
                $tag_ids[] = $tag->id;
            }
        }
        $article->tags()->sync($tag_ids);
    }

    public function shareVideo($id)
    {
        $post = Post::findOrFail($id);
        if (empty($post->video)) {
            return view(
                'errors.404',
                ['data' => "您分享的视频好像不存在呢(。・＿・。)ﾉ"]
            );
        }
        return view('share.shareVideo', [
            'post'    => $post,
            'article' => $post,
            'video'   => $post->video,
            'user'    => $post->user,
        ]);
    }
}
