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

        $this->article = Article::factory([
            'user_id' => $this->user->id
        ])->create();
    }

    /**
     * 文章详情
     * @group article
     * @group testArticleQuery
     */
    public function testArticleQuery()
    {
        $query = file_get_contents(__DIR__ . '/Article/articleQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->article->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 用户的公开文章
     * @group article
     * @group testArticlesQuery
     */
    public function testArticlesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Article/articlesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'user_id' => $this->user->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 删除文章
     * @group article
     * @group testDeleteArticleMutation
     */
    public function testDeleteArticleMutation()
    {
        $query = file_get_contents(__DIR__ . '/Article/deleteArticleMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->article->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }


    /**
     * 用户关注的文章
     * @group article
     * @group testFollowedArticlesQuery
     */
    public function testFollowedArticlesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Article/followedArticlesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'user_id' => $this->user->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 推荐文章
     * @group article
     * @group testRecommendArticlesQuery
     */
    public function testRecommendArticlesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Article/recommendArticlesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [];
        $this->startGraphQL($query,$variables,$headers);
    }
}
