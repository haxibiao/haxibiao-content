<?php

namespace Haxibiao\Content\Traits;

use App\Article;

/**
 * 用户使用文章系统
 */
trait UseArticle
{
    public function articles()
    {
        return $this->hasMany(Article::class)
            ->where('status', '>', Article::STATUS_REVIEW)
            ->exclude(['body', 'json']);
    }

    public function removedArticles()
    {
        return $this->hasMany(Article::class)->where('status', Article::STATUS_REFUSED);
    }

    public function allArticles()
    {
        return $this->hasMany(Article::class)
            ->exclude(['body', 'json']);
    }

    public function allVideoPosts()
    {
        return $this->allArticles()->where('type', 'video');
    }

    public function publishedArticles()
    {
        return $this->allArticles()->where('status', '>', Article::STATUS_REVIEW);
    }

    public function videoPosts()
    {
        return $this->publishedArticles()->where('type', 'video');
    }

    public function videoArticles()
    {
        return $this->hasMany(Article::class)
            ->where('articles.type', 'video');
    }

    public function drafts()
    {
        return $this->hasMany(Article::class)->where('status',Article::STATUS_REVIEW);
    }
}
