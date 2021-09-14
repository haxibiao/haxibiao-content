<?php
namespace Haxibiao\Content\Traits;


use App\Chat;
use App\Image;
use App\OAuth;
use App\User;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Notifications\BreezeNotification;
use Haxibiao\Content\Article;
use Illuminate\Support\Facades\DB;
use function Doctrine\Common\Cache\Psr6\get;

/**
 * Trait Meetable
 * @package Haxibiao\Content\Traits
 */
trait Meetable
{
    public function chat(){
        return $this->hasOne(\App\Chat::class,'article_id');
    }

    // 创建Meetup
    public function resolveCreateMeetup($root, $args, $context, $resolveInfo)
    {
        // 判断类型
        $user = getUser();

        $title        = data_get($args,'title');
        $description  = data_get($args,'description');
        $images       = data_get($args,'images');
        $price        = data_get($args,'price');
        $expiresAt    = data_get($args,'expires_at');
        $expiresAt    = $expiresAt->getTimestamp();
        $address      = data_get($args,'address');

        //判断用户信息是否完整(手机号，微信)
        static::checkUserInfo($user);
        //检查创建约单时间不能迟于当前时间
        static::checkExpiresAtInfo($expiresAt);


        $article = new static();
        $article->title     = $title;
        $article->user_id   = $user->id;
        $article->description = $description;

        $json = [
            'expires_at'   => $expiresAt,
            'price'        => $price,
            'address'      => $address,
            'users'        => [[
                'id'         => $user->id,
                'created_at' => time(),
            ]],
        ];
        $article->json   = $json;
        $article->type   = Article::MEETUP;
        $article->status = Article::STATUS_ONLINE;
        $article->submit = Article::SUBMITTED_SUBMIT;
        $article->save();

        if ($images) {
            $imageIds = [];
            foreach ($images as $image) {
                $model      = Image::saveImage($image);
                $imageIds[] = $model->id;
            }
            $article->images()->sync($imageIds);
        }
        return $article;
    }

    // 加入Meetup
    public function resolveJoinMeetup($root, $args, $context, $info)
    {
        $auth = getUser();
        $meetup_id = data_get($args,'id');
        $article = \App\Article::find($meetup_id);

        //TODO  自己不能报名自己的Meetup

        //检查加入约单时间，不能超过该约单的截止时间
        $expiresAt = data_get($article,'json.expires_at');
        if($article->type == \App\Article::MEETUP){
            throw_if(time() > $expiresAt, GQLException::class , '加入约单时间不能迟于截止时间!!');
        }
        $users = data_get($article,'json.users',array());
        $uids  = data_get($users,'*.id',[]);

        if(in_array($auth->id,$uids)){
            $users = array_filter($users,function ($user)use($auth){
                return data_get($user,'id') != $auth->id;
            });
            $article->forceFill([
                'json->users'=> $users
            ])->save();
            $article->joined = false;
        } else {
            $article->forceFill([
                'json->users'=> array_merge($users,[[
                    'id'         => $auth->id,
                    'created_at' => time(),
                ]])
            ])->save();
            $article->joined = true;
        }
        return $article;
    }

    // Meetups
    public function resolveMeetups($root, $args, $context, $info){
        $perPage     = data_get($args,'first');
        $currentPage = data_get($args,'page');
        $filter      = data_get($args,'filter'); //TODO
        $user_id     = data_get($args,'user_id');

        $qb     = Article::whereType(Article::MEETUP)->when(!blank($user_id),function ($qb)use($user_id){
            return $qb->where('user_id',$user_id);
        })->when(!blank($filter),function($qb) use($filter){
            if($filter == 'latest'){
                return $qb->latest();
            }
        });
        $total  = $qb->count();
        $meetups = $qb->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();
        return new \Illuminate\Pagination\LengthAwarePaginator($meetups, $total, $perPage, $currentPage);
    }

    // 报名者
    public function resolveParticipants($rootValue, $args, $context, $resolveInfo){
        $userIds = data_get($rootValue,'json.users.*.id',[]);
        return User::whereIn('id',$userIds);
    }

    public function resolveMeetup($root, $args, $context, $info){
        $id         = data_get($args,'id');
        $article    = Article::findOrFail($id);
        $league_id  = data_get($args,'league_id');

        //格式化折扣后的价格
        if($league_id){
            $league  = Article::find($league_id);
            $meetups = data_get($league,'json.meetups',[]);
            foreach ($meetups as $meetup){
                if(data_get($meetup,'id') == $id){
                    data_set($article,'price',data_get($meetup,'price'));
                }
            }
        }

        return   $article;
    }

    // 删除Meetup
    public function resolveDeleteMeetup($root, $args, $context, $info){
        $id      = data_get($args,'id');
        $article = Article::findOrFail($id);
        throw_if($article->user_id != getUserId(), GQLException::class,'您没有删除的权限哦～～');
        $article->delete();
        return $article;
    }

    // 进入群聊
    public function resolveJoinGroupChatByMeetupId($root, $args, $context, $resolveInfo){
        $meetupId = data_get($args,'meetup_id');
        $user     = getUser();
        $article  = Article::findOrFail($meetupId);
        $userIds  = data_get($article,'json.users.*.id',[]);
        throw_if(!in_array($user->id,$userIds),new GQLException('进入群聊前请先报名！'));


		$chat     = Chat::where('article_id',$meetupId)->first();
		if($chat){
			return $chat;
		}

        throw_if($user->id == $article->user_id && count($userIds)<2,new GQLException('报名人数达到2人后才可发起群聊！'));

		$userIds = array_merge([$article->user_id], $userIds);
		$userIds = array_unique($userIds);
		sort($userIds);

		return Chat::updateOrCreate([
			'article_id' => $article->id,
		],[
			'subject'        => $article->title,
			'introduction'   => $article->description,
			'uids'      => $userIds,
			'user_id'   => $article->user_id,
			'type'      => Chat::MEET_UP_TYPE,
		]);
    }
    // 更新订单
    public function resolveUpdateMeetup($root, $args, $context, $resolveInfo)
    {
        // 获取用户填入的信息，录入到后台
        $meetupId     = data_get($args,'id');
        $title        = data_get($args,'title');
        $description  = data_get($args,'description');
        $images       = data_get($args,'images');
        $expiresAt    = data_get($args,'expires_at');
        $address      = data_get($args,'address');
        $status       = data_get($args,'status');
        $price        = data_get($args,'price');
        $canJoinLeague = data_get($args,'can_join_league');

        //检查修改约单时间不能迟于当前时间
        if($expiresAt){
            static::checkUpdateExpiresAtInfo($expiresAt);
        }

        $article = Article::findOrFail($meetupId);

        //检查是否为该约单的创建者
        throw_if($article->user_id != getUserId(), GQLException::class,'您没有修改的权限哦！！');

        if(!is_null($title)){
            $article->title = $title;
        }
        if(!is_null($description)){
            $article->description = $description;
        }
        $json = $article->json;
        if(!is_null($expiresAt)){
            data_set($json,'expires_at',$expiresAt->getTimestamp());
        }
        if(!is_null($price)){
            data_set($json,'price',$price);
        }
        if(!is_null($canJoinLeague)){
            data_set($json,'can_join_league',$canJoinLeague);
        }
        if(!is_null($address)){
            data_set($json,'address',$address);
        }
        if(!is_null($status)){
            if($status == 1){
                $article->status = static::STATUS_ONLINE;
                $article->submit = static::SUBMITTED_SUBMIT;
            } else {
                $article->status = static::STATUS_REVIEW;
                $article->submit = static::REVIEW_SUBMIT;
            }
        }
        $article->json = $json;
        $article->saveQuietly();

        if (!is_null($images)) {
            $imageIds = [];
            foreach ($images as $image) {
                $model      = Image::saveImage($image);
                $imageIds[] = $model->id;
            }
            $article->images()->sync($imageIds);
        }
        return $article;
    }

    // 我加入的订单
    public function resolveJoinedMeetups($root, $args, $context, $resolveInfo){
        $user        = getUser();
        $perPage     = data_get($args,'first');
        $currentPage = data_get($args,'page');
        $status      = data_get($args,'status');
        $qb    = Article::whereJsonContains('json->users', [['id' => $user->id]])->whereIn('type',[Article::MEETUP,Article::LEAGUE_OF_MEETUP]);
        if(!blank($status)){
            if($status == 'REGISTERING'){
                $qb = $qb->where("json->expires_at",'>', now()->getTimestamp());
            }
            if($status == 'REGISTERED'){
                $qb = $qb->where("json->expires_at",'<=', now()->getTimestamp());
            }
        }
        $total = $qb->count();
        $meetups = $qb->orderBy('id','desc')->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();
        return new \Illuminate\Pagination\LengthAwarePaginator($meetups, $total, $perPage, $currentPage);
    }

    public function getRegistrationHasClosedAttribute(){
        return \Carbon\Carbon::createFromTimestamp(data_get($this,'json.expires_at'))->isBefore(now());
    }

    public function getCountParticipantsAttribute()
    {
        if(!currentUser()){
            return 0;
        }
        $users = data_get($this,'json.users',[]);
        return count($users);
    }

    public function getJoinedAttribute()
    {
        if(!currentUser()){
            return false;
        }
        $user = getUser();
        $uids = data_get($this,'json.users.*.id',[]);
        if(in_array($user->id,$uids)){
            return true;
        }
        return false;
    }

    public function getExpiresAtAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp(data_get($this,'json.expires_at'));
    }

    public function getAddressAttribute()
    {
        return data_get($this,'json.address');
    }

    //限制每分钟发起约单的次数
    private static function checkMeetupAmount($user)
    {
        $time = strtotime("-1 min");
        $article = Article::where('created_at','>',date('Y-m-d H:i:s', $time))->where('user_id',$user->id)->count();
        throw_if($article > 0 , GQLException::class, '每分钟只能发起一次约单！！');
    }
    //检查用户身份信息
    private static function checkUserInfo($user)
    {
        $role = $user->role_id;
        $phone = $user->phone;
        throw_if($role != User::STAFF_ROLE && $role != User::ADMIN_STATUS , GQLException::class, '必须是员工或者管理员哦！');

        $wechat = OAuth::where('user_id',$user->id)->first();
        throw_if(!$phone || $wechat,GQLException::class,'用户信息不完整，请先补充好信息');
    }
    //检查创建约单时间不能迟于当前时间
    private static function checkExpiresAtInfo($expiresAt)
    {
        throw_if($expiresAt < time(), GQLException::class , '约单时间不能迟于当前时间!!');
    }
    //检查修改约单时间不能迟于当前时间
    private static function checkUpdateExpiresAtInfo($expiresAt)
    {
        throw_if($expiresAt->getTimestamp() < time(), GQLException::class , '约单时间不能迟于当前时间!!');
    }

    /// 联盟约单
    public function getStatusOfJoinLeagueAttribute()
    {
        if(!data_get($this,'json.can_join_league',false)){
            return null;
        }
        $user = getUser(false);

        if(blank($user)){
            return '加入联盟';
        }
        // 不是高权限用户
        if(!$user->isHighRole()){
            return null;
        }
        // 自己
        if($user->id == $this->user_id){
            return null;
        }
        $meetups = data_get($this,'json.meetups',[]);
        foreach ($meetups as $meetup){
            if(data_get($meetup,'user_id') == $user->id){
                if(data_get($meetup,'status') == 0){
                    return "审核中";
                }
                if(data_get($meetup,'status') == 1){
                    return null;
                }
            }
        }
        return '加入联盟';
    }

    public function getOriginalPriceAttribute(){
        return data_get($this,'json.price');
    }

    public function resolveLeagueOfMeetup($root, $args, $context, $info){
        $perPage     = data_get($args,'first');
        $currentPage = data_get($args,'page');
        $filter      = data_get($args,'filter');

        $qb     = static::whereType(\App\Article::LEAGUE_OF_MEETUP)->when(!blank($filter),function($qb) use($filter){
            if($filter != 'ALL'){
                return $qb->whereStatus(1);
            }
        });
        $total  = $qb->count();
        $meetups = $qb->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();
        return new \Illuminate\Pagination\LengthAwarePaginator($meetups, $total, $perPage, $currentPage);
    }

    // 同意加入订单
    public function resolveAgreeToApply($root, $args, $context, $info){
        $dataId = data_get($args,'data_id'); // 这个是偏移量
        $status = data_get($args,'status',1);

        $index  = $dataId; //联盟订单申请列表的偏移量
        $user = getUser();
        $applyInfo = data_get($user,"json.meetups.$index");
        $meetupId = data_get($applyInfo,'meetup_id');;
        $league = static::findOrFail(data_get($applyInfo,'league_id'));

        throw_if(is_null($league),new \Exception('该申请已失效！'));

        // 更新联盟订单的状态
        $meetups = data_get($league,'json.meetups');
        $newMeetups = [];
        foreach ($meetups as $meetup){
            if(data_get($meetup,'id') == $meetupId){
                data_set($meetup,'status',$status);
            }
            $newMeetups[] = $meetup;
        }
        $league->forceFill([
            'json->meetups'=> $newMeetups
        ])->save();

        //更新联盟订单的申请状态
        $newApplyMeetups = data_get($user,'json.meetups');
        data_set($newApplyMeetups,"$index.status",$status);
        $user = $league->user;
        $user->forceFill([
            'json->meetups'=> $newApplyMeetups
        ])->save();

        // 移除通知
        $notificationId = data_get($args,'notification_id',1);
        if($notificationId){
            DB::table('notifications')->where('id',$notificationId)->delete();
        }
        return $league;
    }

    // 联盟约单列表（发起，加入）
    public function resolveLinkLeagueOfMeetup($root, $args, $context, $info){
        $auth  = getUser();
        $meetups     = data_get($root,'json.meetups',[]);
        $perPage     = data_get($args,'first');
        $currentPage = data_get($args,'page');
        $meetupIds = [];
        foreach ($meetups as $meetup){
            $status = data_get($meetup,'status');
            if($status == 1){
                $meetupIds[] = data_get($meetup,'id');
            }
        }
        $qb = static::whereIn('id',$meetupIds);
        $total  = $qb->count();
        $models = $qb->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();

        foreach ($models as $model){
            foreach ($meetups as $meetup){
                if(data_get($meetup,'id') == $model->id){
                    data_set($model,'price',data_get($meetup,'price'));
                }
            }
        }
        return new \Illuminate\Pagination\LengthAwarePaginator($models, $total, $perPage, $currentPage);
    }

    // 我的联盟约单列表
    public function resolveJoinedLeagueOfMeetup($root, $args, $context, $info){
        $auth  = getUser();
        $filter = data_get($args,'filter');
        $perPage     = data_get($args,'first');
        $currentPage = data_get($args,'page');

        $qb = static::where('type',static::LEAGUE_OF_MEETUP)->when($filter == 'INITIATOR',function($qb)use($auth){
            return $qb->where('user_id',$auth->id);
        })->when($filter == 'PARTICIPATOR',function($qb)use($auth){
            return $qb->whereJsonContains('json->meetups', [['user_id' => $auth->id]])
                ->whereType(Article::LEAGUE_OF_MEETUP);
        });

        $total  = $qb->count();
        $meetups = $qb->skip(($currentPage * $perPage) - $perPage)
            ->take($perPage)
            ->get();
        return new \Illuminate\Pagination\LengthAwarePaginator($meetups, $total, $perPage, $currentPage);
    }

    // 加入联盟约单
    public function resolveJoinLeagueOfMeetup($root, $args, $context, $info)
    {
        $auth = getUser();
        $meetupId   = data_get($args,'meetup_id');
        $meetup     = Article::findOrFail($meetupId);
        $price      = data_get($args,'price');
        $leagueId   = data_get($args,'league_id');
        $league     = static::findorFail($leagueId);
        $meetups    = data_get($league,'json.meetups',array());
        $user       = $league->user;
        // 跳过已经申请的联盟订单
        $applyMeetupLists = data_get($user,'json.meetups',[]);
        foreach ($applyMeetupLists as $applyMeetup){
            if( $applyMeetup['league_id'] == $leagueId && $applyMeetup['meetup_id'] == $meetupId ){
                throw new \Exception('抱歉，您已申请过加入该联盟！');
            }
        }
        $user->forceFill([
            'json->meetups'=> array_merge($applyMeetupLists,[[
                'meetup_id' => $meetup->id,
                'league_id' => $league->id,
                'status'    => 0,
            ]])
        ])->saveQuietly();
        $count      = count($applyMeetupLists);
        if($user){
            $user->notify(new BreezeNotification($auth, $count, 'meetups', '申请加入《'.data_get($league,'title').'》联盟', $meetup->cover, $meetup->title, '加入联盟订单'));
        }
        $league->forceFill([
            'json->meetups'=> array_merge($meetups,[[
                'id'         => $meetupId,
                'user_id'    => $meetup->user_id,
                'price'      => $price,
                'status'     => 0,
                'created_at' => time(),
            ]])
        ])->save();
        return $meetup;
    }

    // 创建联盟约单
    public function resolveCreateLeagueOfMeetup($root, $args, $context, $resolveInfo){
        $user = getUser();

        $meetupId     = data_get($args,'meetup_id');
        $meetup       = static::findOrFail($meetupId);
        $title        = data_get($args,'title');
        $description  = data_get($args,'description');
        $canJoinLeague= data_get($args,'can_join_league',true);
        $images       = data_get($args,'images');
        $address      = data_get($args,'address');
        $status       = data_get($args,'status',0);// 默认是下架状态
        $article = new static();
        $article->title     = $title;
        $article->user_id   = $user->id;
        $article->description = $description;
        $json = [
            'address'      => $address,
            'can_join_league'      => $canJoinLeague,
            'users'        => [[
                'id'         => $user->id,
                'created_at' => time(),
            ]],
            "meetups"      => [
                [
                    'id'        => $meetup->id,
                    'user_id'   => $meetup->user_id,
                    'price'     => data_get($args,'price',data_get($meetup,'json.price')),
                    'status'    => 1,
                    'created_at' => time(),
                ]
            ]
        ];
        $article->json   = $json;
        $article->type   = static::LEAGUE_OF_MEETUP;
        if($status == 1){
            $article->status = static::STATUS_ONLINE;
            $article->submit = static::SUBMITTED_SUBMIT;
        }
        $article->save();

        if ($images) {
            $imageIds = [];
            foreach ($images as $image) {
                $model      = Image::saveImage($image);
                $imageIds[] = $model->id;
            }
            $article->images()->sync($imageIds);
        }
        return $article;
    }

    // 退出联盟
    public function resolveLeaveLeagueOfMeetup($root, $args, $context, $resolveInfo){
        $user = getUser();
        $leagueId = data_get($args,'league_id');

        $league   = static::findOrFail($leagueId);
        $meetups  = data_get($league,'json.meetups',[]);
        $newMeetups = [];
        foreach ($meetups as $meetup){
            if(data_get($meetup,'user_id') != $user->id){
                $newMeetups[] = $meetup;
            }
            if($league->user_id == data_get($meetup,'user_id')){
                throw new \Exception('您是联盟发起者，该订单不可移除～');
            }
        }
        $league->forceFill([
            'json->meetups'=> $newMeetups
        ])->save();

        return $league;
    }

    // 用户待审核的订单数
    public function resolveApplyLeagueOfMeetupCount($root, $args, $context, $resolveInfo){
        $user = getUser();
        $meetups = data_get($user,'json.meetups',[]);
        $meetups = array_filter($meetups,function ($meetup){
            return data_get($meetup,'status') == 0;
        });
        return count($meetups);
    }

}
