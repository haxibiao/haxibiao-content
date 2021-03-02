<?php

namespace Haxibiao\Content\Tests\Feature\Api;

use App\Article;
use App\Collection;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiCollectionTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $collection;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user       = User::factory()->create();
        $this->collection = Collection::factory()->create();
        $this->article    = Article::factory()->create();
    }

    /**
     * @group collection
     * 合集首页
     */
    public function testCollectionsIndexApi()
    {
        $user     = $this->user;
        $response = $this->call('GET', '/api/collections', ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collection
     * 合集详情
     */
    public function testShowCollectionApi()
    {
        $id       = $this->collection->id;
        $response = $this->get("/api/collection/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group collection
     * 合集下的文章
     */
    public function testCollectionArticleApi()
    {
        $collection = $this->collection;
        $id         = $collection->id;
        $response   = $this->get("/api/collection/{$id}/articles");
        $response->assertStatus(200);
    }

    /**
     * @group collection
     * 创建合集
     */
    public function testCreateCollectionApi()
    {
        $user                = $this->user;
        $collection          = $this->collection;
        $collection->name    = "api测试合集创建";
        $collection->user_id = 2;
        $data                = $collection->toArray();
        $response            = $this->post("/api/collection/create", $data, ['api_token' => $user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group collection
     * 更新合集
     */
    public function testUpdateCollectionApi()
    {
        $user       = $this->user;
        $collection = $this->collection;
        $id         = $collection->id;
        $response   = $this->post("/api/collection/{$id}", ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collection
     * 删除合集
     */
    public function testDeleteCollectionApi()
    {
        $id       = $this->collection->id;
        $response = $this->delete("/api/collection/{$id}");
        $response->assertStatus(302);
    }

    /**
     * @group collection
     */
    public function testCreateArticleCollectionApi()
    {
        $user           = $this->user;
        $collection     = $this->collection;
        $id             = $collection->id;
        $article        = $this->article;
        $article->title = "测试合集。。";
        $data           = $article->toArray();
        $response       = $this->post("/api/collection/{$id}/article/create", $data, ['api_token' => $user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group collection
     */
    public function testMovieArticleCollectionApi()
    {
        $user     = $this->user;
        $id       = $this->article->id;
        $cid      = $this->collection->id;
        $response = $this->call('GET', "/api/article-{$id}-move-collection-{$cid}", ['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collection
     */
    public function testGetCollectionVideosCollectionApi()
    {
        $collection_id = $this->collection->id;
        $response      = $this->post("/api/collection/{$collection_id}/posts");
        $response->assertStatus(200);
    }

    /**
     * @group collection
     * 分享合集
     */
    public function testShareCollectionWeb()
    {
        $collection = Collection::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $id       = $collection->id;
        $response = $this->call('GET', "/share/collection/{$id}");
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        $this->collection->forceDelete();
        $this->article->forceDelete();
        parent::tearDown();
    }
}
