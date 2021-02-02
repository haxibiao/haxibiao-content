<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Article;
use App\Category;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $category;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = Category::factory([
            'user_id' => $this->user->id,
            'status'  => 1,
        ])->create();
        $this->article = Article::factory([
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ])->create();
    }

    /**
     * @group categoryApi
     * @group testShowCategoryApi
     * 单个专题详情
     */
    public function testShowCategoryApi()
    {
        $id       = $this->category->id;
        $response = $this->get("/api/category/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testIndexCatrgoryApi
     * 专题详情
     */
    public function testIndexCatrgoryApi()
    {
        $response = $this->get("/api/category");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testGetCategoryVideosApi
     * 专题下的视频
     */
    public function testGetCategoryVideosApi()
    {
        $category_id = $this->category->id;
        $response    = $this->get("/api/category/{$category_id}/videos");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testNewLogoCategoryApi
     */
    public function testNewLogoCategoryApi()
    {
        $response = $this->post("/api/category/new-logo");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testEditLogoCategoryApi
     */
    public function testEditLogoCategoryApi()
    {
        $id       = $this->category->id;
        $response = $this->post("/api/category/{$id}/edit-logo");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testUpdateCategoryApi
     */
    public function testUpdateCategoryApi()
    {
        $id       = $this->category->id;
        $response = $this->post("/api/category/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testSubmitCategoryApi
     */
    public function testSubmitCategoryApi()
    {
        $aid      = $this->article->id;
        $cid      = $this->category->id;
        $response = $this->get("/api/category/{$aid}/submit-category-{$cid}");
        $response->assertStatus(200);
    }

    /**
     * @group categoryWeb
     * @group testCategoryWeb
     */
    public function testCategoryWeb()
    {
        $response = $this->post("/category");
        $response->assertStatus(302);
    }

    protected function tearDown(): void
    {
        $this->article->delete();
        $this->category->delete();
        $this->user->delete();
        parent::tearDown();

    }
}
