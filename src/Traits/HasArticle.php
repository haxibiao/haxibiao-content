<?php

namespace Haxibiao\Content\Traits;


trait HasArticle
{
    public function articleModel(): string
    {
        return config('haxibiao-content.models.article');
    }

    public function articles()
    {
        return $this->hasMany($this->articleModel())
            ->where('status', '>', 0)
            ->exclude(['body', 'json']);
    }

    public function removedArticles()
    {
        return $this->hasMany($this->articleModel())->where('status', -1);
    }

    public function allArticles()
    {
        return $this->hasMany($this->articleModel())
            ->exclude(['body', 'json']);
    }

    public function allVideoPosts()
    {
        return $this->allArticles()->where('type', 'video');
    }

    public function publishedArticles()
    {
        return $this->allArticles()->where('status', '>', 0);
    }

    public function videoPosts()
    {
        return $this->publishedArticles()->where('type', 'video');
    }

    public function videoArticles()
    {
        return $this->hasMany($this->articleModel())
            ->where('articles.type', 'video');
    }

    public function drafts()
    {
        return $this->hasMany($this->articleModel())->where('status', 0);
    }
}
