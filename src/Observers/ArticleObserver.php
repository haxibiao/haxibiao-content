<?php

namespace Haxibiao\Content\Observers;

use Haxibiao\Content\Article;
use Haxibiao\Content\Events\MeetupWasUpdated;
use Haxibiao\Content\Traits\ArticleRepo;

class ArticleObserver
{
    public function creating(Article $article)
    {
        // nova create with user id
        if (empty($article->user_id)) {
            $article->user_id = Auth()->id();
        }
        $article->count_words = ceil(strlen(strip_tags($article->body)) / 2);
        if ($article->video) {
            $article->cover_path = $article->video->cover;
        }
    }

    public function created(Article $article)
    {
        $user = data_get($article, 'user');
        if ($user) {
            //黑名单用户禁言处理
            if ($user->isBlack()) {
                // $article->delete();
                $article->status = Article::STATUS_REFUSED;
                $article->save();
                // throw new GQLException('发布失败,你以被禁言');

            }

            if ($profile = $user->profile) {
                $profile->count_articles = $article->user->publishedArticles()->count();
                $profile->count_words    = $article->user->publishedArticles()->sum('count_words');
                $profile->save();
            }
        }

        if ($category = $article->category) {
            $category->count = $category->articles()->count();
            $category->save();
            //同步多对多的关系
            $article->hasCategories()->syncWithoutDetaching([$category->id]);
        }

        if ($article->status == Article::STATUS_ONLINE) {
            //可能是发布了文章，需要统计文集的文章数，字数
            if ($collection = $article->collection) {
                $collection->count       = $collection->articles()->count();
                $collection->count_words = $collection->articles()->sum('count_words');
                $collection->save();
            }
        }

        //处理图片
        ArticleRepo::saveRelatedImagesFromBody($article);
    }

    public function updated(Article $article)
    {
        //TODO: 更多需要更新文章数和字数的场景需要写这里...
        //TODO: 文章软删除时
        if ($article->status = Article::STATUS_REVIEW) {
            $article->update([
                'submit' => Article::REFUSED_SUBMIT,
            ]);
        }

        //处理图片
        ArticleRepo::saveRelatedImagesFromBody($article);
    }

    public function deleted(Article $article)
    {
        //TODO：文章彻底删除
    }

    public function restored(Article $article)
    {
        //
    }

    public function forceDeleted(Article $article)
    {
        //
    }

    public function saved($article)
    {
        if (data_get($article, 'type') == Article::MEETUP) {
            event(new MeetupWasUpdated($article));
        }
    }
}
