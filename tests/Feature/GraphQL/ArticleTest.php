<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Article;
use App\Category;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticleTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->article = Article::factory(['user_id' => $this->user->id])->create();
    }

    /**
     * 我关注的用户的文章/菜谱
     * @group testFollowedArticleQuery
     * @group article
     */
    public function testFollowedArticleQuery()
    {
        $category = Category::factory()->create();
        Article::factory(3)->create([
            'category_id' => $category->id
        ]);

        $follower = User::factory()->create();
        $follower->followIt($category);
        $headers = $this->getRandomUserHeaders($follower);

        $query     = file_get_contents(__DIR__ . '/article/followedArticlesQuery.gql');
        $variables = [
            'user_id' => $follower->id,
        ];
        $this->startGraphQL($query, $variables ,$headers);
    }

    /**
     * 通过id查询文章/菜谱详情
     * @group testArticleQuery
     * @group article
     */
    public function testArticleQuery()
    {
        $query     = file_get_contents(__DIR__ . '/article/articleQuery.gql');
        $variables = [
            'id' => $this->article->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 通过id查询文章/菜谱详情
     * @group testArticlesQuery
     * @group article
     */
    public function testArticlesQuery()
    {
        $query = file_get_contents(__DIR__ . '/article/articlesQuery.gql');

        //用户公开的文章
        $variables = [
            'submit' => "SUBMITTED_SUBMIT",
        ];
        $this->startGraphQL($query, $variables);

        //用户全部文章
        $variables = [
            'submit' => "ALL",
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  article
     * @group testUserFavoriteArticlesQuery
     */
    public function testUserFavoriteArticlesQuery()
    {
        $user  = $this->user;
        $query = file_get_contents(__DIR__ . '/article/userFavoriteArticlesQuery.gql');

        $token   = $user->api_token;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $variables = [
            'type' => "ARTICLE",
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 推荐文章/菜谱
     * @group testRecommendArticlesQuery
     * @group article
     */
    public function testRecommendArticlesQuery()
    {
        $query     = file_get_contents(__DIR__ . '/article/recommendArticlesQuery.gql');
        $variables = [
            'count' => 1,
            'page'  => 1,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  article
     * @group testDeleteArticleMutation
     */
    public function testDeleteArtcleMutation()
    {

        $query = file_get_contents(__DIR__ . '/article/deleteArticleMutation.gql');

        $token   = $this->user->api_token;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $variables = [
            'id' => $this->article->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        $this->article->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }

}
