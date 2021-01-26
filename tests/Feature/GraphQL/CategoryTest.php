<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Category;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;

class CategoryTest extends GraphQLTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected $user;

    protected $category;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('id', '<', 100)->inRandomOrder()->first();
        $this->category = Category::where('id', '<', 100)->inRandomOrder()->first();
    }

    /**
     * 分类详情
     * @group category
     * @group testCategoryQuery
     */
    public function testCategoryQuery()
    {
        $query = file_get_contents(__DIR__ . '/Category/categoryQuery.gql');
        $categoryId = Category::inRandomOrder()->first();
        $variables = [
            'id' => $categoryId->id
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
}
