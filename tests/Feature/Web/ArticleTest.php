<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Article;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();

        //随机拿个测试用户
        $this->user = User::factory()->create();
        //先确保创建了文章
        $this->article = Article::factory([
            'user_id' => $this->user->id,
            'status'  => 1,
        ])->create();
    }

    public function testStorePost()
    {
        $user         = $this->user;
        $article      = $this->article;
        $categories   = \App\Category::orderBy('id', 'desc')->take(10)->get();
        $category_ids = [];
        foreach ($categories as $category) {
            $category_ids[] = $category->id;
        }
        $video = \App\Video::inRandomOrder()->first();
        $data  = ['categories' => $categories, 'video_id'        => $video->id, 'qcvod_fileid' => $video->qcvod_fileid,
            'body'                 => $article->body, 'category_ids' => $category_ids];
        $response = $this->actingAs($user)
            ->post("/post/new", $data);
        $response->assertStatus(302);
    }

    public function testArticleDrafts()
    {
        $user     = $this->user;
        $response = $this->actingAs($user)->get("/drafts");
        $response->assertStatus(200);
    }

    public function testShareVideo()
    {
        $post_id  = \App\Post::orderBy('id', 'desc')->take(10)->get()->random()->id;
        $response = $this->get("/share/post/$post_id");
        $response->assertStatus(200);
    }

    public function testArticleIndex()
    {
        $user     = $this->user;
        $response = $this->actingAs($user)->get("/article");
        $response->assertStatus(200);
    }

    public function testArticleShow()
    {
        $id       = $this->article->id;
        $response = $this->get("/article/{$id}");
        $response->assertStatus(200);
    }

    public function testArticleCreate()
    {
        $user     = $this->user;
        $response = $this->actingAs($user)->get("/article/create");
        $response->assertStatus(200);
    }

    public function testArticleStored()
    {
        $user           = $this->user;
        $article        = $this->article;
        $article->id    = null;
        $article->title = 'testArticle';
        $article->body  = 'testBody123456977855488';
        $response       = $this->actingAs($user)->post("/article", $article->toArray());
        $response->assertStatus(str_contains(data_get($user, 'email', ''), '@haxibiao.com') ? 302 : 403);
    }

    public function testArticleEdit()
    {
        $response = $this->actingAs($this->user)->get("/article/{$this->article->id}/edit");
        $response->assertStatus(200);
    }

    public function testArticleUpdate()
    {
        $this->article->title = $this->article->title . " - 编辑于" . now()->toDateString();
        $response             = $this->actingAs($this->user)->put("/article/{$this->article->id}", $this->article->toArray());
        $response->assertStatus(302);
    }

    public function testArticleDestroy()
    {
        $response = $this->actingAs($this->user)->delete("/article/{$this->article->id}");
        $response->assertStatus(302);
    }

}
