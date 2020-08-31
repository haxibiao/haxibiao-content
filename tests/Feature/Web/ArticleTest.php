<?php

namespace Haxibiao\Content\Tests\Feature\Web;


use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class ArticleTest extends TestCase
{

    use DatabaseTransactions;

    public function testStorePost()
    {
        $user = User::inRandomOrder()->first();
        $article = \App\Article::inRandomOrder()->first();
        $categories = \App\Category::orderBy('id', 'desc')->take(10)->get();
        $category_ids = [];
        foreach ($categories as $category) {
            $category_ids[] = $category->id;
        }
        $video = \App\Video::inRandomOrder()->first();
        $data = ['categories' => $categories, 'video_id' => $video->id, 'qcvod_fileid' => $video->qcvod_fileid,
            'body' => $article->body, 'category_ids' => $category_ids];
        $response = $this->actingAs($user)
            ->post("/post/new", $data);
        $response->assertStatus(302);
    }


    public function testDrafts()
    {
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $response = $this->actingAs($user)->get("/drafts");
        $response->assertStatus(200);
    }

    public function testShareVideo()
    {
        $post_id = \App\Post::orderBy('id', 'desc')->take(10)->get()->random()->id;
        $response = $this->get("/share/post/$post_id");
        $response->assertStatus(200);
    }

    public function testIndex()
    {
        $user = User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/article");
        $response->assertStatus(200);
    }

    public function testShow()
    {
        $article = \App\Article::inRandomOrder()->first();
        $id = $article->id;
        $response = $this->get("/article/{$id}");
        $response->assertStatus(302);
    }

    public function testCreate()
    {
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $response = $this->actingAs($user)->get("/article/create");
        $response->assertStatus(200);
    }

    public function testStored()
    {
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $article = \App\Article::inRandomOrder()->first();
        $article->id = null;
        $article->title = 'testArticle';
        $article->body = 'testBody123456977855488';
        $response = $this->actingAs($user)->post("/article", $article->toArray());
        $response->assertStatus(str_contains(data_get($user, 'email', ''), '@haxibiao.com') ? 302 : 403);
    }

    public function testEdit()
    {
        $id = \App\Article::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/article/{$id}/edit");
        $response->assertStatus(checkEditor() ? 200 : 404);
    }

    public function testUpdate()
    {
        $id = \App\Article::inRandomOrder()->first()->id;
        $article = \App\Article::inRandomOrder()->first();
        $article->id = null;
        $user = User::where('role_id', User::EDITOR_STATUS)->first();
        $response = $this->actingAs($user)->put("/article/{$id}", $article->toArray());
        $response->assertStatus(302);
    }

    public function testDestroy()
    {
        $id = \App\Article::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->actingAs($user)->delete("/article/{$id}");
        $response->assertStatus(302);
    }

}