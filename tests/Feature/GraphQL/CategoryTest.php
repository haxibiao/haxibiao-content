<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Category;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

    protected function tearDown(): void
    {
        $this->category->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }
}
