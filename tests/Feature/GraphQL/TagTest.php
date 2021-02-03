<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Post;
use Haxibiao\Breeze\GraphQLTestCase;
use App\User;
use App\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TagTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $tag;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tag = Tag::factory()->create([
            'user_id' => $this->user->id,
            'name'    => '测试标签 - name',
        ]);
    }

    /**
     * 热门标签
     * @group tag
     * @group testHotTagQuery
     */
    public function testHotTagQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Tag/hotTagQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);

        // 登录
        $variables = [];
        $this->startGraphQL($query, $variables,$headers);

        // 未登录
        $variables = [];
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 搜索标签
     * @group tag
     * @group testSearchTagsQuery
     */
    public function testSearchTagsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Tag/searchTagsQuery.graphql');
        $tag       = $this->tag;
        $variables = [
            'query' => $tag->name,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 标签下的内容
     * @group tag
     * @group testTagPostsQuery
     */
    public function testTagPostsQuery()
    {
        $tag = $this->tag;
        $user = $this->user;
        $posts = Post::factory(5)->create();
        $postIds = array();
        foreach ($posts as $post){
            $postIds[$post->id] = [
                'tag_name'    => $tag->name,
                'user_id' => $user->id,
            ];
        }
        $tag->posts()->sync($postIds);
        $query     = file_get_contents(__DIR__ . '/Tag/tagPostsQuery.graphql');
        $variables = [
            'tag_id' => $tag->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 标签详情
     * @group tag
     * @group testTagQuery
     */
    public function testTagQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Tag/tagQuery.graphql');
        $tag       = $this->tag;
        $variables = [
            'id' => $tag->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 标签列表
     * @group tag
     * @group testTagsQuery
     */
    public function testTagsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Tag/tagsQuery.graphql');
        $variables = [
            'filter' => 'HOT',
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 用户的标签
     * @group tag
     * @group testUserTags
     */
    public function testUserTags()
    {
        $user = $this->user;
        $query     = file_get_contents(__DIR__ . '/Tag/userTags.graphql');
        $variables = [
            'id' => $user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    protected function tearDown(): void
    {
        $this->tag->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }
}
