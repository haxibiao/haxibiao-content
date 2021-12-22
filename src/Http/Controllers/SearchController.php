<?php

namespace Haxibiao\Content\Http\Controllers;

use App\Article;
use App\Category;
use App\Collection;
use App\Query;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as LCollection;
use MeiliSearch\Endpoints\Indexes;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $page_size = 10;
        $page      = request('page') ? request('page') : 1;
        $query     = get_kw();

        $qbSearch = Article::search($query);

        //FIXME: 最新内容靠前，需要meilisearch更新到v0.24+并配置成功 $index->updateSortableAttributes()
        if (config('scout.meilisearch.sortable')) {
            $qbSearch = Article::search($query, function (Indexes $index, string $query, array $options) {
                return $index->search($query, array_merge($options, ['sort' => ['id:desc']]));
            });
        }

        $articles = $qbSearch->orderBy('id', 'desc')
            ->paginate(10);
        $total = $articles->total();

        //保存搜索的关键词记录
        save_kw($query, $total);

        //高亮关键词
        foreach ($articles as $article) {
            $article->title       = str_replace($query, '<em>' . $query . '</em>', $article->title);
            $article->description = str_replace($query, '<em>' . $query . '</em>', $article->summary);
        }

        //如果标题无结果，搜索标签库
        if (!$total) {
            list($articles_taged, $matched_tags) = $this->search_tags($query);
            $total                               = count($articles_taged);

            //给标签搜索到的分页
            $articles = new LengthAwarePaginator($articles_taged->forPage($page, $page_size),
                $total, $page_size, $page, ['path' => '/search']);

            //高亮标签
            foreach ($articles as $article) {
                $article->description = ' 关键词:' . $article->keywords . '， 简介：' . $article->summary;
                foreach ($matched_tags as $tag) {
                    $article->title       = str_replace($tag, '<em>' . $tag . '</em>', $article->title);
                    $article->description = str_replace($tag, '<em>' . $tag . '</em>', $article->description);
                }
            }
        }

        //用户 专题 电影搜索(前3即可)
        $data['users'] = $page > 1 ? [] : User::where('name', 'like', "%$query")
            ->where('status', '>=', 0)
            ->take(3)->get();
        $data['categories'] = $page > 1 ? [] : Category::where('name', 'like', "%$query%")
            ->where('status', '>=', 0)
            ->orderBy('id', 'asc')
            ->take(3)->get();
        $data['movies'] = $page > 1 ? [] : \App\Movie::searchQuery($query)->paginate(3);

        $data['articles'] = $articles;
        $data['query']    = $query;
        $data['total']    = $total;

        return view('search.articles')->withData($data);
    }

    public function searchVideos()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = get_kw();
        $data['video'] = Article::whereType('video')
            ->whereStatus(1)
            ->where('title', 'like', "%$query%")
            ->paginate($page_size);
        $data['query'] = $query;

        return view('search.video')->withData($data);
    }

    public function searchMovies()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = get_kw();
        $data['movie'] = \App\Movie::searchQuery($query)->paginate($page_size);
        $data['query'] = $query;
        $total         = count($data['movie']);

        //保存搜索的关键词记录
        save_kw($query, $total);

        return view('search.movie')->withData($data);
    }

    public function searchUsers()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = get_kw();
        $data['users'] = User::where('status', '>=', 0)->where('name', 'like', "%$query%")->paginate($page_size);
        $data['query'] = $query;
        return view('search.users')->withData($data);
    }

    public function searchCategories()
    {
        $page_size          = 10;
        $page               = request('page') ? request('page') : 1;
        $query              = get_kw();
        $data['categories'] = Category::where('status', '>=', 0)
            ->where('name', 'like', "%$query%")
            ->orderBy('id', 'asc')
            ->paginate($page_size);
        $data['query'] = $query;
        return view('search.categories')->withData($data);
    }

    public function searchCollections()
    {
        $page_size           = 10;
        $page                = request('page') ? request('page') : 1;
        $query               = get_kw();
        $data['collections'] = Collection::where('status', '>=', 0)
            ->where('name', 'like', "%$query%")
            ->paginate($page_size);
        $data['query'] = $query;
        return view('search.collections')->withData($data);
    }

    public function search_tags($query)
    {
        $articles     = [];
        $tags         = Tag::all();
        $matched_tags = [];
        foreach ($tags as $tag) {
            if ($query && str_contains($query, $tag->name)) {
                foreach ($tag->articles as $article) {
                    $articles[] = $article;
                }
                $matched_tags[] = $tag->name;
            }
        }
        return [LCollection::make($articles), $matched_tags];
    }

    public function searchQuery()
    {
        $users          = User::all();
        $querys         = Query::where('status', '>=', 0)->orderBy('hits', 'desc')->paginate();
        $data           = [];
        $data['update'] = Query::where('status', '>=', 0)->orderBy('updated_at', 'desc')->paginate(10);
        return view('search.queries')
            ->withData($data)
            ->withUsers($users)
            ->withQuerys($querys);
    }
}
