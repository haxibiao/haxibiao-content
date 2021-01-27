<?php

namespace Haxibiao\Content\Tests\Feature\Api;


use App\Issue;
use App\Solution;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiIssueTest extends TestCase
{
    use DatabaseTransactions;


    /**
     * @group apiIssue
     * @group testSuggest
     */
    public function testSuggest()
    {
        $user = User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/suggest-question"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testQuestion
     */
    public function testQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/question/{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testReportQuestion
     */
    public function testReportQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/report-question-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testFavoriteQuestion
     */
    public function testFavoriteQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/favorite-question-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testAnswer
     */
    public function testAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/answer/{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testLikeAnswer
     */
    public function testLikeAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/like-answer-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testUnlikeAnswer
     */
    public function testUnlikeAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/unlike-answer-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testReportAnswer
     */
    public function testReportAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/report-answer-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testDeleteAnswer
     */
    public function testDeleteAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/delete-answer-{$id}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }


    /**
     * @group apiIssue
     * @group testQuestionUninvited
     */
    public function testQuestionUninvited()
    {
        $issue_id = Issue::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/question-{$issue_id}-uninvited"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testQuestionInvite
     */
    public function testQuestionInvite()
    {
        $qid = Issue::inRandomOrder()->first()->id;
        $invite_uid = \App\User::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/question-{$qid}-invite-user-{$invite_uid}"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(201);
    }

    /**
     * @group apiIssue
     * @group testAnswered
     */
    public function testAnswered()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('POST', "/api/question-{$id}-answered"
        , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testDelete
     */
    public function testDelete()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->call('GET', "/api/delete-question-{$id}"
        , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testCommend
     */
    public function testCommend()
    {
        $response = $this->get("/api/commend-question");
        $response->assertStatus(200);
    }
}