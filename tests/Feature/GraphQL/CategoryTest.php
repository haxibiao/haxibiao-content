<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Category;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionFunctionAbstract;
use Tests\CreatesApplication;

class CategoryTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $category;

    public function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * 分类详情
     * @group category
     * @group testCategoryQuery
     */
    public function testCategoryQuery()
    {
        $query      = file_get_contents(__DIR__ . '/Category/categoryQuery.gql');
        $categoryId = $this->category;
        $variables  = [
            'id' => $categoryId->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 热门分类
     * @group category
     * @group testCategoriesQuery
     */
    public function testCategoriesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Category/categoriesQuery.gql');

        //hot分类
        $variables = [
            'filter' => "hot",
        ];
        $this->startGraphQL($query, $variables);

        //other分类
        $variables = [
            'filter' => "other",
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 推荐文章的专题分类
     * @group category
     * @group testArticleCategoriesQuery
     */
    public function testArticleCategoriesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Category/articleCategoriesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'page' => 1,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 按组分类的专题列表
     * @group category
     * @group testFilteredCategoriesQuery
     */
    public function testFilteredCategoriesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Category/filteredCategoriesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'filter' => 'hot'
        ];
        $this->startGraphQL($query,$variables,$headers);

        $variables = [
            'filter' => 'other'
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    protected function tearDown(): void
    {
        $this->category->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }
}
