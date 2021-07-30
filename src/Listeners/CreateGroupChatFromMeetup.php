<?php

namespace Haxibiao\Content\Listeners;

use App\Chat;

class CreateGroupChatFromMeetup
{
    public function handle($event)
    {
        $article = $event->article;
        $uids    = data_get($article,'json.users.*.id');

        $uids = array_merge([$article->user_id], $uids);
        $uids = array_unique($uids);
        sort($uids);

        Chat::updateOrCreate([
            'article_id' => $article->id,
        ],[
            'subject'        => $article->title,
            'introduction'   => $article->description,
            'uids'      => $uids,
            'user_id'   => $article->user_id,
            'type'      => Chat::MEET_UP_TYPE,
        ]);
    }
}
