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



    /**
     * @group categoryApi
     * @group testRequestedArticlesApi
     */
    // public function testRequestedArticlesApi()
    // {
    //     $cid = Category::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/requested-articles-{$cid}");
    //     $response->assertStatus(200);
    // }


    /**
     * @group categoryApi
     * @group testPendingArticlesApi
     */
    // public function testPendingArticlesApi()
    // {
    //     $response = $this->get("/api/category/pending-articles");
    //     $response->assertStatus(200);
    // }


    /**
     * @group categoryApi
     * @group testNewReuqestCategoriesApi
     */
    // public function testNewReuqestCategoriesApi()
    // {
    //     $response = $this->get("/api/category/new-requested");
    //     $response->assertStatus(200);
    // }

    /**
     * @group categoryApi
     * @group testRecommendCategoriesCheckArticleApi
     */
    // public function testAdminCategoriesCheckArticleApi()
    // {
    //     $aid = Article::inRandomOrder()->first()->id;
    //     // $cid = Category::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/admin-check-article-{$aid}");
    //     $response->assertStatus(200);
    // }

    /**
     * @group categoryApi
     * @group testRecommendCategoriesCheckArticleApi
     */
    // public function testRecommendCategoriesCheckArticleApi()
    // {
    //     $aid = Article::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/recommend-check-article-{$aid}");
    //     $response->assertStatus(200);
    // }


    /**
     * @group categoryApi
     * @group testApproveCategoryApi
     */
    // public function testApproveCategoryApi()
    // {
    //     $aid = Article::inRandomOrder()->first()->id;
    //     $cid = Category::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/approve-category-{$cid}-{$aid}");
    //     $response->assertStatus(200);
    // }

    //Route::get('/category/{aid}/add-category-{cid}', 'CategoryController@addCategory');
    /**
     * @group categoryApi
     * @group testAddCategoryApi
     */
    // public function testAddCategoryApi()
    // {
    //     $aid = Article::inRandomOrder()->first()->id;
    //     $cid = Category::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/{$aid}/add-category-{$cid}");
    //     $response->assertStatus(200);
    // }

    /**
     * @group categoryApi
     * @group testCheckCategoryApi
     * 专题投稿
     */
    // public function testCheckCategoryApi()
    // {
    //     $id = Category::inRandomOrder()->first()->id;
    //     $response = $this->get("/api/category/check-category-{$id}");
    //     $response->assertStatus(200);
    // }


    /**
     * @group categoryApi
     * @group testSearchCategoryApi
     * 搜索专题
     */
    // public function testSearchCategoryApi()
    // {
    //     $aid = Category::inRandomOrder()->first()->name;
    //     $response = $this->get("/api/category/search-submit-for-article-{$aid}");
    //     $response->assertStatus(200);
    // }

    /**
     * @group categoryApi
     * @group testPageCategoryApi
     */
    // public function testPageCategoryApi()
    // {
    //     $response = $this->get("/api/category/recommend-categories");
    //     $response->assertStatus(200);
    // }


    /**
     * @group categoryWeb
     * @group testListCategoryWeb
     */
    // public function testListCategoryWeb()
    // {
    //     $response = $this->get("/list");
    //     $response->assertStatus(200);
    // }

    /**
     * @group categoryApi
     * @group testNewRequestCategorysApi
     * 新投稿请求列表
     */
    // public function testNewRequestCategorysApi()
    // {
    //     $response = $this->get("/api/category/new-requested");
    //     $response->assertStatus(200);
    // }
}