<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Collection;
use App\Post;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CollectionTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $collection;
    protected $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user       = User::factory()->create();
        $this->collection = Collection::factory([
            'user_id'     => $this->user->id,
            'status'      => 1,
            'type'        => 'posts',
            'name'        => '测试合集数据 - name',
            'description' => '测试合集数据 - description',
            'logo'        => Collection::TOP_COVER(),
            'sort_rank'   => 1,
        ])->create();
        $this->post = Post::factory()->create();
    }

    /**
     * 合集内的视频
     * @group collection
     * @group testCollectionPostsQuery
     */
    public function testCollectionPostsQuery(){
        $query     = file_get_contents(__DIR__ . '/CollectionGraphql/collectionPostsQuery.gql');
        $variables = [
            'collection_id' => $this->collection->id,
        ];
        $this->startGraphQL($query, $variables);
    }
    /**
     * 我的合集
     * @group collection
     * @group testCollectionsQuery
     */
    public function testCollectionsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/CollectionGraphql/collectionsQuery.gql');
        $variables = [
            'user_id' => $this->user->id,
        ];
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 合集详情
     * @group collection
     * @group testCollectionQuery
     */
    public function testCollectionQuery()
    {
        $query      = file_get_contents(__DIR__ . '/CollectionGraphql/collectionQuery.gql');
        $collection = $this->collection;
        $variables  = [
            'collection_id' => $collection->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 添加合集
     * @group collection
     * @group testMoveInCollectionsMutation
     */
    public function testMoveInCollectionsMutation()
    {
        $query      = file_get_contents(__DIR__ . '/CollectionGraphql/moveInCollectionsMutation.gql');
        $collection = $this->collection;
        $post       = $this->post;
        $variables  = [
            "collection_id"   => $collection->id,
            "collectable_ids" => [$post->id],
        ];
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 添加动态到合集/从合集中移除动态
     * @group collection
     * @group testMoveOutCollectionsMutation
     */
    public function testMoveOutCollectionsMutation()
    {
        $queryIn     = file_get_contents(__DIR__ . '/CollectionGraphql/moveInCollectionsMutation.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $collection  = $this->collection;
        //往合集中添加视频
        $post        = $this->post;
        $variablesIn = [
            "collection_id"   => $collection->id,
            "collectable_ids" => [$post->id],
        ];
        $this->startGraphQL($queryIn, $variablesIn, $userHeaders);
        //将视频从合集中移除
        $queryOut     = file_get_contents(__DIR__ . '/CollectionGraphql/moveOutCollectionsMutation.gql');
        $post         = $collection->posts()->first();
        $variablesOut = [
            "collection_id"   => $collection->id,
            "collectable_ids" => [$post->id],
        ];
        $this->startGraphQL($queryOut, $variablesOut, $userHeaders);
    }

    /**
     * 随机查询合集
     * @group collection
     * @group testRandomCollectionsMutation
     */
    public function testRandomCollectionsMutation()
    {
        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/randomCollectionsQuery.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $variables   = [];
        // 登陆
        $this->startGraphQL($query, $variables, $userHeaders);
        // 没登录
        $this->startGraphQL($query, $variables, []);
    }

    /**
     * 搜索合集
     * @group collection
     * @group testSearchCollectionsQuery
     */
    public function testSearchCollectionsQuery()
    {
        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/searchCollectionsQuery.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $collection  = $this->collection;
        $variables   = [
            'query' => $collection->name,
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 删除合集
     * @group collection
     * @group testDeleteCollectionMutation
     */
    public function testDeleteCollectionMutation()
    {
        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/DeleteCollectionMutation.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $collection  = $this->collection;
        $variables   = [
            "id" => $collection->id,
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 编辑合集
     * @group collection
     * @group testEditCollectionMutation
     */
    public function testEditCollectionMutation()
    {
        $query      = file_get_contents(__DIR__ . '/CollectionGraphql/editCollectionMutation.gql');
        $collection = $this->collection;
        $variables  = [
            "collection_id" => $collection->id,
            "name"          => "测试修改",
        ];
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 创建合集
     * @group collection
     * @group testCreateCollectionMutation
     */
    public function testCreateCollectionMutation()
    {
        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/createCollectionMutation.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $post        = $this->post;
        //创建时添加合集
        $variables = [
            'name'            => "测试",
            "collectable_ids" => [$post->id],
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
        //创建时不添加合集
        $variables = [
            'name' => "测试",
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 分享合集
     * @group collection
     * @group testShareCollectionQuery
     */
    public function testShareCollectionQuery()
    {
        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/shareCollectionQuery.gql');
        $collection  = $this->collection;
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $variables   = [
            'collection_id' => $collection->id,
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * @group collection
     * @group testTypeCollectionsQuery
     */
    public function testTypeCollectionsQuery()
    {
        $query = file_get_contents(__DIR__ . '/CollectionGraphql/typeCollectionsQuery.gql');

        // POST @enum(value: "psots")
        $variables = [
            'user_id' => $this->user->id,
            'type'    => 'POST',
        ];
        $this->startGraphQL($query, $variables);

        // ARTICLE @enum(value: "articles")
        $variables = [
            'user_id' => $this->user->id,
            'type'    => 'ARTICLE',
        ];
        $this->startGraphQL($query, $variables);

        // AREA @enum(value: "areas")
        $variables = [
            'user_id' => $this->user->id,
            'type'    => 'AREA',
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 置顶合集推荐
     * @group collection
     * @group testRecommendCollectionsQuery
     */
    public function testRecommendCollectionsQuery(){

        $query       = file_get_contents(__DIR__ . '/CollectionGraphql/recommendCollectionsQuery.gql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $variables   = [];
        // 登陆
        $this->startGraphQL($query, $variables, $userHeaders);
        // 没登录
        $this->startGraphQL($query, $variables, []);
    }

    protected function tearDown(): void
    {
        $this->post->forceDelete();
        $this->collection->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }
}
