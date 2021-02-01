<?php

namespace Haxibiao\Content\Traits;

use App\Exceptions\GQLException;
use App\Image;
use App\Issue;
use App\IssueInvite;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Breeze\Notifications\QuestionInvited;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait IssueResolvers
{

    public function createIssueResolver($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {

        $user = getUser();
        //创建一个新的问题issue
        $this->title      = $args['title'];
        $this->background = $args['background'];
        $this->user_id    = $user->id;
        $this->save();

        if (!empty($args['cover_image'])) {
            $image = Image::saveImage($args['cover_image']);
            $this->images()->attach($image->id);
            $this->save();

        }

        return $this;
    }

    public function searchIssueResolver($rootValue, $args, $context, $resolveInfo)
    {

        $query = $args['query'];

        $issues = Issue::where('title', 'like', '%' . $query . '%')
            ->orWhere('background', 'like', '%' . $query . '%')
            ->orderBy('created_at', 'desc');
        //高亮关键词
        foreach ($issues as $issue) {
            $issue->title      = str_replace($query, '<em>' . $query . '</em>', $issue->title);
            $issue->background = str_replace($query, '<em>' . $query . '</em>', $issue->description);
        }
        return $issues;

    }
    public function issueResolver($rootValue, $args, $context, $resolveInfo)
    {

        $field = array_get($args, 'orderBy.0.field') ?: 'created_at';
        $order = array_get($args, 'orderBy.0.order') ?: 'DESC';
        $qb    = Issue::orderBy($field, $order);
        //用户的问答黑名单
        if (checkUser()) {
            //获取登录用户
            $user     = getUser();
            $issueIds = $user->userBlock()->where('block_type', 'issues')->pluck("block_id");
            $qb->whereNotIn('id', $issueIds);
        }
        return $qb;

    }
    public function inviteAnswerResolver($rootValue, $args, $context, $resolveInfo)
    {

        $invited_user_id = $args['invited_user_id'];
        $issue_id        = $args['issue_id'];
        $user            = getUser();
        $invite_user     = User::find($invited_user_id);
        if ($invite_user) {
            $invite = IssueInvite::firstOrNew([
                'user_id'        => $user->id,
                'issue_id'       => $issue_id,
                'invite_user_id' => $invited_user_id,
            ]);
            //避免重复发消息
            if (!$invite->id) {
                $invite_user->notify(new QuestionInvited($user->id, $issue_id));
            } else {

                //手动更新下updated_at
                $invite->updated_at = $invite->freshTimestamp();
            }

            $invite->save();
        }
        return $invite;
    }

    public function deleteIssueResolver($rootValue, $args, $context, $resolveInfo)
    {
        $user     = getUser();
        $issue_id = $args['issue_id'];
        $issue    = Issue::findOrFail($issue_id);
        $user_id  = $issue->user_id;
        throw_if($user_id != $user->id, GQLException::class, '删除失败，该问题不是你发布的');
        $issue->delete();
        return $issue;
    }
}
