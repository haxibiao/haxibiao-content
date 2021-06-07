<?php

namespace Haxibiao\Content\Http\Api;

use App\Article;
use App\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        //限制最多加载最近的100个合集
        $collections = $user->hasCollections()
            ->whereType(Collection::TYPE_OF_ARTICLE)
            ->where('status','>',Collection::STATUS_SELECTED)
            ->latest('id')
            ->take(100)
            ->get();
        //每个合集限制最多加载最近100篇文章
        foreach ($collections as $collection) {
            $collection->articles = $collection->articles()->latest('id')->take(100)->get();
        }
        return $collections;
    }

    public function show(Request $request, $id)
    {
        return Collection::findOrFail($id);
    }

    public function articles(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $articles   = $collection->articles()->with('user')->orderBy(request('collected') ? 'created_at' : 'updated_at', 'desc')->paginate(10);
        foreach ($articles as $article) {
            $article->user        = $article->user->fillForJs();
            $article->description = $article->summary;
        }
        return $articles;
    }

    public function create(Request $request)
    {
        $collection          = new Collection($request->all());
        $collection->user_id = $request->user()->id;
        $collection->type    = \App\Collection::TYPE_OF_ARTICLE;
        $collection->save();
        $collection->load('articles');
        return $collection;
    }

    public function update(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->update($request->all());
        return $collection;
    }

    public function delete(Request $request, $id)
    {
        $collection         = Collection::findOrFail($id);
        $collection->status = Collection::STATUS_DELETED;
        $collection->save();

        //delete articles to trash
        foreach ($collection->articles as $article) {
            $article->status = Article::STATUS_REFUSED;
            $article->save();
        }

        return $collection;
    }

    public function moveArticle(Request $request, $id, $cid)
    {
        $article                = Article::findOrFail($id);
        $article->collection_id = $cid;
        $article->timestamps    = false;
        $article->save();

        $article->addCollections(Arr::wrap($id));

        return $article;
    }

    public function createArticle(Request $request, $id)
    {
        $article                = new Article($request->all());
        $article->user_id       = $request->user()->id;
        $article->collection_id = $id;
        $article->save();

        $article->updateCollections(Arr::wrap($id));

        return $article;
    }

    //获取指定合集下的动态
    public function getCollectionVideos($collection_id)
    {
        $video_id = request()->get('video_id');
        $num      = request()->get('num') ? request()->get('num') : 10;
        $posts    = [];
        if ($collection = Collection::find($collection_id)) {
            $posts = $collection->posts()->with('video')
                ->where('video_id', '!=', $video_id)
                ->whereStatus(Collection::STATUS_ONLINE)
                ->paginate($num);
            foreach ($posts as $post) {
                $post->fillForJs();
            }
        }
        return $posts;
    }
}
