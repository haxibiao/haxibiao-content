<?php

namespace Haxibiao\Content\Http\Api;

use App\Http\Controllers\Controller;
use App\Issue;
use App\IssueInvite;
use App\Solution;
use App\Transaction;
use App\User;
use Haxibiao\Breeze\Notifications\QuestionBonused;
use Haxibiao\Breeze\Notifications\QuestionDelete;
use Haxibiao\Breeze\Notifications\QuestionInvited;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    //相似问答
    public function suggest(Request $request)
    {
        $questions = [];
        if (!empty(request('q'))) {
            $questions = Issue::where('title', 'like', '%' . request('q') . '%')->take(10)->get();
        }
        return $questions;
    }

    //问题可以邀请用户列表,七天内只能邀请一次
    public function questionUninvited(Request $request, $issue_id)
    {
        $users = User::latest('updated_at')->take(10)->get();
        foreach ($users as $user) {
            $user->fillForJs();
            $user->invited = 0;
        }
        return $users;
    }

    //邀请用户
    public function questionInvite(Request $request, $qid, $invite_uid)
    {
        $user        = $request->user();
        $invite_user = User::find($invite_uid);
        if ($invite_user) {
            $invite = IssueInvite::firstOrNew([
                'user_id'        => $user->id,
                'issue_id'       => $qid,
                'invite_user_id' => $invite_uid,
            ]);
            //避免重复发消息
            if (!$invite->id) {
                $invite_user->notify(new QuestionInvited($user->id, $qid));
            } else {
                //手动更新下updated_at
                $invite->updated_at = $invite->freshTimestamp();
            }

            $invite->save();
        }
        return $invite;
    }

    //采纳答案
    public function answered(Request $request, $id)
    {
        $issue = Issue::findOrFail($id);

        //确保采纳了一些答案
        if (is_array($request->answered_ids) && count($request->answered_ids)) {

            //fix error, vue send answered_ids as json array
            $resolution_ids = [];
            foreach ($request->answered_ids as $resolution_id) {
                if (is_numeric($resolution_id)) {
                    $resolution_ids[] = $resolution_id;
                } else {
                    //TODO ???
                    $resolution_ids[] = $resolution_id['answerId'];
                }
            }

            $issue->resolution_ids = implode(',', $resolution_ids);
            $issue->closed         = true;
            $issue->save();

            //选中的回答者分奖金，发消息
            $bonus_each = floor($issue->bonus / count($request->answered_ids) * 100) / 100;
            foreach ($issue->selectedAnswers() as $answer) {
                $answer->bonus = $bonus_each;
                $answer->save();
                if ($issue->bonus && $issue->bonus > 0) {
                    //到账
                    Transaction::create([
                        'user_id' => $answer->user->id,
                        'type'    => '付费回答奖励',
                        'amount'  => $bonus_each,
                        'status'  => '已到账',
                        'balance' => $answer->user->balance + $bonus_each,
                    ]);
                }
                //消息
                $answer->user->notify(new QuestionBonused($issue->user, $issue));
            }
        }

        return $issue;
    }

    public function question(Request $request, $id)
    {
        $question = Issue::findOrFail($id);
        return $question;
    }

    public function favoriteQuestion(Request $request, $id)
    {
        $issue = Issue::findOrFail($id);
        $issue->count_favorites++;
        $issue->save();
        $issue->favorited = 1;

        //收藏问答暂时不实现网站用户通知...
        // $controller = new \Haxibiao\Sns\Http\Api\FavoriteController();
        // $controller->toggle($request, $id, 'issues');

        return $issue;
    }

    public function reportQuestion(Request $request, $id)
    {
        $issue = Issue::findOrFail($id);
        $issue->count_reports++;
        $issue->save();
        $issue->reported = 1;
        return $issue;
    }

    public function answer(Request $request, $id)
    {
        $solution = Solution::findOrFail($id);
        return $solution;
    }

    public function likeAnswer(Request $request, $id)
    {
        $solution = Solution::findOrFail($id);
        $solution->count_likes++;
        $solution->save();
        $solution->liked = 1;
        return $solution;
    }

    public function unlikeAnswer(Request $request, $id)
    {
        $solution = Solution::findOrFail($id);
        $solution->count_unlikes++;
        $solution->save();
        $solution->unliked = 1;
        return $solution;
    }

    public function reportAnswer(Request $request, $id)
    {
        $solution = Solution::findOrFail($id);
        $solution->count_reports++;
        $solution->save();
        $solution->reported = 1;
        return $solution;
    }

    public function deleteAnswer(Request $request, $id)
    {
        $solution             = Solution::findOrFail($id);
        $solution->status     = -1;
        $solution->deleted_at = now();
        $solution->save();
        return $solution;
    }
    public function delete(Request $request, $id)
    {
        $issue = Issue::findOrFail($id);
        if ($issue->bonus > 0 && !$issue->close) {
            //自动奖励前10个回答
            $top10Resolutions = $issue->solutions()->take(10)->get();
            //分奖金(保留两位，到分位)，发消息r

            if (!$top10Resolutions) {
                $bonus_each = floor($issue->bonus / $top10Resolutions->count() * 100) / 100;
                foreach ($top10Resolutions as $resolution) {
                    $resolution->bonus = $bonus_each;
                    $resolution->save();
                    //到账
                    Transaction::create([
                        'user_id' => $resolution->user->id,
                        'type'    => '付费回答奖励',
                        'remark'  => $issue->link() . '选中了您的回答',
                        'amount'  => $bonus_each,
                        'status'  => '已到账',
                        'balance' => $resolution->user->balance + $bonus_each,
                    ]);
                    //消息
                    $resolution->user->notify(new QuestionBonused($issue->user, $issue));
                    //通知已经结账
                    $issue->user->notify(new QuestionDelete($issue));
                }
                //标记已回答
                $issue->resolutions_id = implode($top10Resolutions->pluck('id')->toArray(), ',');
            } else {
                Transaction::create([
                    'user_id' => $issue->user->id,
                    'type'    => '退回问题奖金',
                    'remark'  => $issue->link() . '您的问题无人回答',
                    'amount'  => $issue->bonus,
                    'status'  => '已到账',
                    'balance' => $issue->user->balance + $issue->bonus,
                ]);

                $issue->user->notify(new QuestionDelete($issue));

            }
        }

        //注释原因：issue中无status字段
        //$issue->status = -1;
        $issue->save();

        if ($issue->bonus > 0) {
            if ($issue->answered_ids) {
                $issue->message = "您的问题已删除,并且已结账";
            } else {
                $issue->message = "您的问题已删除,奖金已退回";
            }

        } else {
            $issue->message = "您的问题已删除";
        }
        $issue->delete();
        return $issue;
    }

    public function commend(Request $request)
    {
        $issues = Issue::orderBy('hits', 'desc')->where('image1', '<>', null)->take(3)->get();

        foreach ($issues as $issue) {
            $issue->image1 = $issue->image();
        }

        return $issues;
    }
}
