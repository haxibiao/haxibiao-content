<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Article;
use App\Collection;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class CollectionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @group collectionApi
     * @group testCollectionsIndexApi
     * 合集首页
     */
    public function testCollectionsIndexApi()
    {
        $user = User::inRandomOrder()->first();
        $response = $this->call('GET','/api/collections',['api_token' => $user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collectionApi
     * @group testShowCollectionApi
     * 合集详情
     */
    public function testShowCollectionApi()
    {
        $id = Collection::inRandomOrder()->first()->id;
        $response = $this->get("/api/collection/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group collectionApi
     * @group testCollectionArticleApi
     * 合集下的文章
     */
    public function testCollectionArticleApi()
    {
        $collection = Collection::inRandomOrder()->first();
        $id = $collection->id;
        $response = $this->get("/api/collection/{$id}/articles");
        $response->assertStatus(200);
    }

    /**
     * @group collectionApi
     * @group testCreateCollectionApi
     * 创建合集
     */
    public function testCreateCollectionApi()
    {
        $user = User::inRandomOrder()->first();
        $collection = Collection::inRandomOrder()->first();
        $collection->name = "api测试合集创建";
        $collection->user_id = 2;
        $data = $collection->toArray();
        $response = $this->post("/api/collection/create",$data,['api_token'=>$user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group collectionApi
     * @group testUpdateCollectionApi
     * 更新合集
     */
    public function testUpdateCollectionApi()
    {
        $user = User::inRandomOrder()->first();
        $collection = Collection::inRandomOrder()->first();
        $id = $collection->id;
        $response = $this->post("/api/collection/{$id}",['api_token'=>$user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collectionApi
     * @group testDeleteCollectionApi
     * 删除合集
     */
    public function testDeleteCollectionApi()
    {
        $id = Collection::inRandomOrder()->first()->id;
        $response = $this->delete("/api/collection/{$id}");
        $response->assertStatus(302);
    }

    /**
     * @group collectionApi
     * @group testCreateArticleCollectionApi
     */
    public function testCreateArticleCollectionApi()
    {
        $user = User::inRandomOrder()->first();
        $collection = Collection::inRandomOrder()->first();
        $id = $collection->id;
        $article = Article::inRandomOrder()->first();
        $article->name = "测试合集。。";
        $data = $article->toArray();
        $response = $this->post("/api/collection/{$id}/article/create",$data,['api_token'=>$user->api_token]);
        $response->assertStatus(302);
    }

    /**
     * @group collectionApi
     * @group testMovieArticleCollectionApi
     */
    public function testMovieArticleCollectionApi()
    {
        $user = User::inRandomOrder()->first();
        $id = Article::inRandomOrder()->first()->id;
        $cid = Collection::inRandomOrder()->first()->id;
        $response = $this->call('GET',"/api/article-{$id}-move-collection-{$cid}",['api_token'=>$user->api_token]);
        $response->assertStatus(200);
    }

    /**
     * @group collectionApi
     * @group testGetCollectionVideosCollectionApi
     */
    public function testGetCollectionVideosCollectionApi()
    {
        $collection_id = Collection::inRandomOrder()->first()->id;
        $response = $this->post("/api/collection/{$collection_id}/posts");
        $response->assertStatus(200);
    }

    /**
     * @group collectionWeb
     * @group testShareCollectionWeb
     * 分享合集
     */
    public function testShareCollectionWeb()
    {
        $id = Collection::inRandomOrder()->first()->id;
        $response = $this->call('GET',"/share/collection/{$id}");
        $response->assertStatus(200);
    }

    /**
     * @group collectionWeb
     * @group testCollectionWeb
     */
    public function testCollectionWeb()
    {
        $response = $this->post("/collection");
        $response->assertStatus(200);
    }
}