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

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
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
        $response = $this->get("/issue");
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueStore
     */
    public function testIssueStore()
    {
        $issue             = new \App\Issue;
        $issue->user_id    = 1;
        $issue->title      = "测试";
        $issue->background = "测试";
        $data              = $issue->toArray();
        $response          = $this->post("/issue", $data
            , ['api_token' => $this->user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group webIssue
     * @group testIssueShow
     */
    public function testIssueShow()
    {
        $response = $this->get("/issue/{$this->issue->id}");
        $response->assertStatus(200);
    }

    /**
     * @group webIssue
     * @group testIssueDestroy
     */
    public function testIssueDestroy()
    {
        $response = $this->delete("/issue/{$this->issue->id}");
        $response->assertStatus(302);
    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        $this->issue->forceDelete();
        parent::tearDown();
    }
}
