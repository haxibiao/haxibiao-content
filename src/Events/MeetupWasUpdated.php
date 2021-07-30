<?php


namespace Haxibiao\Content\Events;


class MeetupWasUpdated
{
    public $article;

    public function __construct($article)
    {
        $this->article = $article;
    }
}
