<?php

namespace Haxibiao\Content\Http\Controllers;

use App\Article;
use App\Category;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = [];
        $site = cms_get_site();

        // 置顶 - 电影
        if ($site && $site->stickyMovies()->byStickableName('视频页-电影')->count()) {
            $movies = $site->stickyMovies()
                ->byStickableName('视频页-电影')
                ->latest('stickables.updated_at')
                ->take(6)
                ->get();
        } else {
            $movies = indexTopMovies(6);
        }

        //置顶 - 抖音合集
        $collections = \App\Collection::latest('updated_at')->take(6)->get();

        //置顶 - 电影图解
        if ($site) {
            $articles = $site->stickyArticles()->whereType('diagrams')
                ->byStickableName('视频页-电影图解')
                ->latest('stickables.updated_at')
                ->take(6)
                ->get();
        } else {
            $articles = Article::query()->whereType('diagrams')
                ->latest('updated_at')
                ->take(6)
                ->get();
        }

        return view('video.index')
            ->with('data', $data)
            ->with('videos', [])
            ->with('collections', $collections)
            ->with('articles', $articles)
            ->with('movies', $movies);
    }

    function list(Request $request) {
        $videos = Article::with('user')
            ->with('category')
            ->with('video')
            ->orderBy('id', 'desc')
            ->where('status', '>=', Article::STATUS_REVIEW)
            ->where('type', '=', 'video');

        //Search videos
        $data['keywords'] = '';
        if ($request->get('q')) {
            $keywords         = $request->get('q');
            $data['keywords'] = $keywords;
            $videos           = Article::with('user')
                ->with('category')
                ->with('video')
                ->orderBy('id', 'desc')
                ->where('status', '>=', Article::STATUS_REVIEW)
                ->where(function ($query) use ($keywords) {
                    $query->where('title', 'like', "%$keywords%")
                        ->orWhere('description', 'like', "%$keywords%");
                });
        }
        $videos         = $videos->paginate(10);
        $data['videos'] = $videos;
        return view('video.list')->withData($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['video_categories'] = Category::pluck('name', 'id');
        return view('video.create')->withData($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //TODO: 创建post
        return redirect()->to('/post');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::has('video')->with('collection')->findOrFail($id);

        // //因为APP二维码分享用了 /post/{id} - 需要暂时兼容article 查询
        // if ($article = $post->ar) {
        //     return redirect()->to($article->url);
        // }

        // dd($post->collection);
        $data['related_page'] = request()->get('related_page') ?? 0;

        //暂时用video.show兼容视频动态
        return view('video.show')
            ->with('video', $post->video)
            ->with('post', $post)
            ->with('data', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

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

    }

    /**
     * 删除视频是软删除，同时删除磁盘上的视频文件
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        //软删除 post
        $post->status = -1;
        $post->save();
        return redirect()->to('/post/list');
    }

}
