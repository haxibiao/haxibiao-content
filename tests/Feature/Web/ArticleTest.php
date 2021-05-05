<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Article;
use App\Category;
use App\Post;
use App\User;
use App\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $article;
    protected $post;
    protected $video;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->article = Article::factory([
            'user_id' => $this->user->id,
            'status'  => 1,
        ])->create();
        $this->video = Video::factory(['user_id' => $this->user->id])->create();
        $this->post  = Post::factory([
            'video_id' => $this->video->id, //测试分享短视频分享用
            'user_id'  => $this->user->id,
            'status'   => 1,
        ])->create();

    }

    public function testStorePost()
    {
        $user    = $this->user;
        $article = $this->article;

        //关联专题
        $categories   = Category::factory(3)->create();
        $category_ids = [];
        foreach ($categories as $category) {
            $category_ids[] = $category->id;
        }

        $video = $this->video;
        $data  = [
            'categories'   => $categories,
            'video_id'     => $video->id,
            'fileid'       => $video->fileid,
            'body'         => $article->body,
            'category_ids' => $category_ids,
        ];

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
        $post_id  = $this->post->id;
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

    public function testArticleStore()
    {
        $user           = $this->user;
        $article        = new Article;
        $article->title = 'test store new Article';
        $article->body  = 'test Body 123456977855488 中文测试';
        $response       = $this->actingAs($user)->post("/article", $article->toArray());
        $response->assertStatus(302);
    }

    public function testArticleEdit()
    {
        //seed的3个用户里有编辑用户，才可以编辑别人的文章
        $editor = User::factory()->create([
            'role_id' => User::EDITOR_STATUS,
        ]);
        $response = $this->actingAs($editor)->get("/article/{$this->article->id}/edit");
        $response->assertStatus(200);
    }

    public function testArticleUpdate()
    {
        $this->article->title = $this->article->title . " - 编辑于" . now()->toDateString();
        $response             = $this->actingAs($this->user)
            ->put("/article/{$this->article->id}", $this->article->toArray());
        $response->assertStatus(302);
    }

    public function testArticleDestroy()
    {
        $response = $this->actingAs($this->user)->delete("/article/{$this->article->id}");
        $response->assertStatus(302);
    }

    protected function tearDown(): void
    {
        $this->article->forceDelete();
        $this->post->forceDelete();
        $this->user->forceDelete();
        $this->video->forceDelete();
        parent::tearDown();
    }

}
