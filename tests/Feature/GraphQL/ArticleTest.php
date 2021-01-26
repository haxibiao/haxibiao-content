<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Article;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticleTest extends GraphQLTestCase
{
    /**
     * todo: article->image 字段查询错误，待修复
     */

    use DatabaseTransactions;

    protected $user;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('id', '<', 100)->inRandomOrder()->first();
        //先确保创建了文章
        Article::factory()->count(1)->create();
        $this->article = Article::latest('id')->first();
    }

    /**
     * 我关注的用户的文章/菜谱
     * @group testFollowedArticleQuery
     * @group article
     */
    public function testFollowedArticleQuery()
    {
        $query     = file_get_contents(__DIR__ . '/article/followedArticlesQuery.gql');
        $variables = [
            'user_id' => $this->user->id,
            'type'    => 'users',
        ];
        $this->runGQL($query, $variables);
    }

    /**
     * 通过id查询文章/菜谱详情
     * @group testArticleQuery
     * @group article
     */
    public function testArticleQuery()
    {
        $query     = file_get_contents(__DIR__ . '/article/articleQuery.gql');
        $article   = Article::latest('id')->first();
        $variables = [
            'id' => $article->id,
        ];
        $response = $this->runGQL($query, $variables);
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
        $this->runGQL($query, $variables);

        //用户全部文章
        $variables = [
            'submit' => "ALL",
        ];
        $this->runGQL($query, $variables);
    }

    /**
     * @group  article
     * @group testUserFavoriteArticlesQuery
     */
    public function testUserFavoriteArticlesQuery()
    {
        $user  = User::find(1);
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
     * @group  article
     * @group  testRecommendVideosQuery
     */
    public function testRecommendVideosQuery()
    {
        $token   = User::find(1)->api_token;
        $query   = file_get_contents(__DIR__ . '/article/recommendVideosQuery.gql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $this->startGraphQL($query, [], $headers);
        $this->startGraphQL($query, [], []);
    }

    /**
     * @group  article
     * @group  testShareQuery
     */
    public function testShareQuery()
    {
        $token   = User::find(1)->api_token;
        $query   = file_get_contents(__DIR__ . '/article/shareQuery.gql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $variables = [
            'id' => $this->article->id,
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
        $this->runGuestGQL($query, $variables);
    }

    //todo fix :ut错误
    /**
     * @group  article
     * @group testDeleteArticleMutation
     */
    // public function testDeleteArtcleMutation()
    // {
    //     $post = Post::find(2);
    //     $query = file_get_contents(__DIR__ . '/article/deleteArticleMutation.gql');

    //     $token = User::find(1)->api_token;
    //     $headers = [
    //         'Authorization' => 'Bearer ' . $token,
    //         'Accept' => 'application/json',
    //     ];
    //     $variables = [
    //         'id' => $post->id,
    //     ];
    //     $this->startGraphQL($query, $variables, $headers);
    // }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

}
