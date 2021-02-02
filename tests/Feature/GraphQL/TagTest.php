<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Tag;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TagTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $tag;

    public function setUp(): void
    {
        parent::setUp();

        $this->tag = Tag::create([
            'user_id' => rand(1, 3),
            'name'    => '测试标签 - name',
        ]);
    }

    /**
     * 标签详情
     * @tag
     * @testTagQuery
     */
    public function testTagQuery()
    {
        $query     = file_get_contents(__DIR__ . '/tag/tagQuery.gql');
        $tag       = $this->tag;
        $variables = [
            'id' => $tag->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @tag
     * @testTagsQuery
     */
    public function testTagsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/tag/tagsQuery.gql');
        $variables = [
            'filter' => 'HOT',
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 搜索标签
     * @group tag
     * @group testSearchTagsQuery
     */
    public function testSearchTagsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/tag/searchTagsQuery.gql');
        $tag       = $this->tag;
        $variables = [
            'query' => $tag->name,
        ];
        $this->startGraphQL($query, $variables);
    }

    protected function tearDown(): void
    {
        $this->tag->forceDelete();
        parent::tearDown();
    }
}
