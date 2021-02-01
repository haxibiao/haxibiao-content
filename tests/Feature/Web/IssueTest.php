<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Issue;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IssueTest extends TestCase
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
    }

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
    protected function testIssueStore()
    {
        $issue        = new \App\Issue;
        $issue->user_id = 1;
        $issue->title =  "测试";
        $issue->background =  "测试";
        $data = $issue->toArray();
        $response = $this->post("/question",$data
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group webIssue
     * @group testIssueShow
     */
    public function testIssueShow()
    {
        $response = $this->get("/question/{$this->issue->id}");
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueDestroy
     */
    protected function testIssueDestroy()
    {
        $response = $this->delete("/question/{$this->issue->id}");
        $response->assertStatus(302);
    }
}
