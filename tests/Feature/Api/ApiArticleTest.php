<?php

namespace Haxibiao\Content\Tests\Feature\Api;

use App\Article;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiArticleTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->article = Article::factory(['user_id' => $this->user->id])->create();
    }

    /**
     * @group article
     */
    public function testIndex()
    {
        $user     = $this->user;
        $response = $this->actingAs($user)->get("/api/article");
        $response->assertStatus(200);
    }

    /**
     * @group article
     */
    public function testStore()
    {
        $user           = $this->user;
        $article        = new Article;
        $article->title = 'testTitle';
        $article->body  = 'testtesttesttesttesttest';
        $data           = $article->toArray();

        $response = $this->actingAs($user)->post("/api/article/create", $data, [
            "Authorization" => "Bearer " . $user->api_token,
        ]);
        $response->assertCreated();

        //tearDown
        $article->delete();
    }

    /**
     * @group article
     */
    public function testShow()
    {
        $article  = $this->article;
        $id       = $article->id;
        $response = $this->get("/api/article/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group article
     */
    public function testUpdate()
    {
        $user           = $this->user;
        $article        = $this->article;
        $article->title = $article->title . "[$user->name 编辑]";
        $data           = $article->toArray();

        $response = $this->actingAs($user)->put("/api/article/{$article->id}/update", $data, [
            "Authorization" => "Bearer " . $user->api_token,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'title' => $article->title,
        ]);
    }

    /**
     * @group article
     */
    public function testDestroy()
    {
        $article  = $this->article;
        $user     = $this->user;
        $response = $this->actingAs($user)->call('GET', "/api/article/{$article->id}/destroy"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        $this->article->delete();
        $this->user->delete();
        parent::tearDown();
    }
}
