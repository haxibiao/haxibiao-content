<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IssueTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * @group webIssue
     * @group testCategories
     */
    public function testCategories()
    {
        $response = $this->get('/categories-for-question');
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testBonused
     */
    public function testBonused()
    {
        $response = $this->get('/question-bonused');
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueIndex
     */
    public function testIssueIndex()
    {
        $response = $this->get("/question");
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueStore
     */
    public function testIssueStore()
    {
        $user     = \App\User::inRandomOrder()->first();
        $user = \App\User::inRandomOrder()->first();
        $issue        = new \App\Issue;
        $issue->user_id = 1;
        $issue->title =  "测试";
        $issue->background =  "测试";
        $data = $issue->toArray();
        $response = $this->post("/question",$data
            , ['api_token' => $user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group webIssue
     * @group testIssueShow
     */
    public function testIssueShow()
    {
        $id       = \App\Issue::inRandomOrder()->first()->id;
        $response = $this->get("/question/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueDestroy
     */
    public function testIssueDestroy()
    {
        $id       = \App\Issue::inRandomOrder()->first()->id;
        $response = $this->delete("/question/{$id}");
        $response->assertStatus(302);
    }

    /**
     * @group webIssue
     * @group testSolution
     */
    public function testSolution()
    {
        $user         = \App\User::inRandomOrder()->first();
        $solution        = new \App\Solution;
        $solution->user_id = 1;
        $solution->issue_id =  7;
        $solution->answer =  "测试";
        $data           = $solution->toArray();
        $response     = $this->post("/answer", $data,[
            "Authorization" => "Bearer " . $user->api_token,
        ]);
        $response->assertStatus(302);
    }
}
