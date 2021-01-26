<?php

namespace Haxibiao\Content\Tests\Feature\Api;

use Haxibiao\Breeze\User;
use Haxibiao\Content\Article;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiArticleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @group article
     */
    public function testIndex()
    {
        $user     = User::latest('id')->first();
        $response = $this->actingAs($user)->get("/api/article");
        $response->assertStatus(200);
    }

    /**
     * @group article
     */
    public function testStore()
    {
        $user           = User::role(User::EDITOR_STATUS)->first();
        $article        = new Article;
        $article->title = 'testTitle';
        $article->body  = 'testtesttesttesttesttest';
        $data           = $article->toArray();

        $response = $this->actingAs($user)->post("/api/article/create", $data, [
            "Authorization" => "Bearer " . $user->api_token,
        ]);
        //api创建文章后201 （created）
        // $response->assertStatus(201);
        $response->assertCreated();
    }

    /**
     * @group article
     */
    public function testShow()
    {
        //先创建文章
        $this->testStore();
        //再获取
        $article  = Article::latest('id')->first();
        $id       = $article->id;
        $response = $this->get("/api/article/{$id}");
        $response->assertStatus($article->video_id ? 302 : 200);
    }

    /**
     * @group article
     */
    public function testUpdate()
    {
        $user = User::role(User::EDITOR_STATUS)->first();
        //先创建文章
        $this->testStore();

        $article        = Article::latest('id')->first();
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
        //先创建
        $this->testStore();
        $article = Article::latest('id')->first();
        //小编有权限
        $user     = User::role(User::EDITOR_STATUS)->first();
        $response = $this->actingAs($user)->call('GET', "/api/article/{$article->id}/destroy"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }
}
