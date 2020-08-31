<?php

namespace Haxibiao\Content\Tests\Feature\Api;

use App\Article;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiArticleTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex()
    {

        $user = \App\User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/api/article");
        $response->assertStatus(200);
    }

    public function testShow()
    {
        $article = \App\Article::orderBy('id', 'desc')->where('status', '>', 0)->take(10)->get()->random();
        $id = $article->id;
        $response = $this->get("/api/article/{$id}");
        $response->assertStatus($article->video_id ? 302 : 200);
    }


    public function testStore()
    {
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $article = \App\Article::inRandomOrder()->first();
        $article->id = null;
        $article->title = 'testTitle';
        $article->body = 'testtesttesttesttesttest';
        $data = $article->toArray();

        $response = $this->actingAs($user)->post("/api/article/create", $data, [
            "Authorization" => "Bearer " . $user->api_token
        ]);
        $response->assertStatus(str_contains(data_get($user, 'email', ''), '@haxibiao.com') ? 302 : 403);
    }


    public function testUpdate()
    {
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $id = \App\Article::inRandomOrder()->first()->id;
        $article = \App\Article::inRandomOrder()->first();
        $article->id = null;
        $data = $article->toArray();

        $response = $this->actingAs($user)->put("/api/article/{$id}/update", $data, [
            "Authorization" => "Bearer " . $user->api_token
        ]);
        $response->assertStatus(302);
    }

    public function testDestroy()
    {
        $id = \App\Article::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->actingAs($user)->call('GET', "/api/article/{$id}/destroy"
            , ['api_token' => $user->api_token]);
        $response->assertStatus(302);
    }
}