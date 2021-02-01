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

    protected $user;
    protected $issue;
    protected $solution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::inRandomOrder()->first();
        $this->issue = Issue::create([
            'user_id'    => $this->user->id,
            'title'      => '测试问答',
            'background' => '测试的问答描述',
        ]);
        $this->solution = Solution::create([
            'user_id'    => $this->user->id,
            'issue_id'   => $this->issue->id,
            'answer'     => '问答测试用例'
        ]);
    }

    /**
     * @group apiIssue
     * @group testSuggest
     */
    public function testSuggest()
    {
        $response = $this->call('GET', "/api/suggest-question"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testQuestion
     */
    public function testQuestion()
    {
        $response = $this->call('GET', "/api/question/{$this->issue->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testReportQuestion
     */
    public function testReportQuestion()
    {
        $response = $this->call('GET', "/api/report-question-{$this->issue->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testFavoriteQuestion
     */
    public function testFavoriteQuestion()
    {
        $response = $this->call('GET', "/api/favorite-question-{$this->issue->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testAnswer
     */
    public function testAnswer()
    {
        $response = $this->call('GET', "/api/answer/{$this->solution->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testLikeAnswer
     */
    public function testLikeAnswer()
    {
        $response = $this->call('GET', "/api/like-answer-{$this->solution->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testUnlikeAnswer
     */
    public function testUnlikeAnswer()
    {
        $response = $this->call('GET', "/api/unlike-answer-{$this->solution->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testReportAnswer
     */
    public function testReportAnswer()
    {
        $response = $this->call('GET', "/api/report-answer-{$this->solution->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testDeleteAnswer
     */
    public function testDeleteAnswer()
    {
        $response = $this->call('GET', "/api/delete-answer-{$this->solution->id}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }


    /**
     * @group apiIssue
     * @group testQuestionUninvited
     */
    public function testQuestionUninvited()
    {
        $response = $this->call('GET', "/api/question-{$this->issue->id}-uninvited"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testQuestionInvite
     */
    public function testQuestionInvite()
    {
        $invite_uid = \App\User::inRandomOrder()->first()->id;
        $response = $this->call('GET', "/api/question-{$this->issue->id}-invite-user-{$invite_uid}"
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(201);
    }

    /**
     * @group apiIssue
     * @group testAnswered
     */
    public function testAnswered()
    {
        $response = $this->call('POST', "/api/question-{$this->issue->id}-answered"
        , ['api_token' => $this->user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group apiIssue
     * @group testDelete
     */
    public function testDelete()
    {
        $response = $this->call('GET', "/api/delete-question-{$this->issue->id}"
        , ['api_token' => $this->user->api_token]);
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