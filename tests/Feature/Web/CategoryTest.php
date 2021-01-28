<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Category;
use Haxibiao\Content\Article;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @group categoryApi
     * @group testShowCategoryApi
     * 单个专题详情
     */
    public function testShowCategoryApi()
    {
        $id = Category::inRandomOrder()->first()->id;
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
        $category_id = Category::inRandomOrder()->first()->id;
        $response = $this->get("/api/category/{$category_id}/videos");
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
        $id = Category::inRandomOrder()->first()->id;
        $response = $this->post("/api/category/{$id}/edit-logo");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testUpdateCategoryApi
     */
    public function testUpdateCategoryApi()
    {
        $id = Category::inRandomOrder()->first()->id;
        $response = $this->post("/api/category/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group categoryApi
     * @group testSubmitCategoryApi
     */
    public function testSubmitCategoryApi()
    {
        $aid = Article::inRandomOrder()->first()->id;
        $cid = Category::inRandomOrder()->first()->id;
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
}