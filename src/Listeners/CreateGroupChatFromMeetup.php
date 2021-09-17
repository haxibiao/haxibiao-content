<?php

namespace Haxibiao\Content\Listeners;

use App\Chat;

class CreateGroupChatFromMeetup
{
    public function handle($event)
    {
        // 创建商户与普通用户群
        $article = $event->article;
        $uids    = data_get($article,'json.users.*.id');

        $meetups = data_get($article,'json.meetups',[]);
        $meetups = array_filter($meetups,function ($meetup){
            return data_get($meetup,'status') == 1;
        });
        $storeUids = data_get($meetups,'*.user_id',[]);

        $uids = array_merge([$article->user_id], $uids,$storeUids);
        $uids = array_unique($uids);
        sort($uids);

        // 创建商户+普通用户群
        Chat::updateOrCreate([
            'article_id' => $article->id,
            'type'      => Chat::MEET_UP_TYPE,
        ],[
            'subject'        => $article->title,
            'introduction'   => $article->description,
            'uids'      => $uids,
            'user_id'   => $article->user_id,
        ]);

        // 创建商户总群
        if(count($storeUids) <= 1){
            return;
        }
        Chat::updateOrCreate([
            'article_id' => $article->id,
            'type'      => Chat::BUSINESS_ALLIANCE_TYPE,
        ],[
            'subject'        => sprintf('%s的商户群',$article->title),
            'introduction'   => $article->description,
            'uids'      => $storeUids,
            'user_id'   => $article->user_id,
            'type'      => Chat::MEET_UP_TYPE,
        ]);
    }
}
