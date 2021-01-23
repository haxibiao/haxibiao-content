<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Article;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticleTest extends GraphQLTestCase
{

    protected $user;
    protected $article;
    use DatabaseTransactions;
    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::where('id', '<', 100)->inRandomOrder()->get();
        $this->article = Article::where('id', '<', 100)->inRandomOrder()->get();
    }

    /**
     * 我关注的用户的文章/菜谱
     * @group testFollowedArticleQuery
     * @group article
     */
    public function testFollowedArticleQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Query/followedArticleQuery.gql');
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
        $query     = file_get_contents(__DIR__ . '/Article/Query/articleQuery.gql');
        $articles  = Article::inRandomOrder()->first();
        $variables = [
            'id' => $articles->id,
        ];
        $this->runGQL($query, $variables);
    }

    /**
     * 推荐文章/菜谱
     * @group testRecommendArticlesQuery
     * @group article
     */
    public function testRecommendArticlesQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Query/recommendArticlesQuery.gql');
        $variables = [
            'count' => 1,
            'page'  => 1,
        ];
        $this->runGuestGQL($query, $variables);
    }

    /**
     * 文章下的菜谱/我的菜谱
     * @group testRecipesArticleQuery
     * @group article
     */
    public function testRecipesArticleQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Query/recipesArticleQuery.gql');
        $user      = User::inRandomOrder()->first();
        $variables = [
            'user_id' => $user->id,
            'page'    => 1,
        ];
        $this->runGuestGQL($query, $variables);
    }

    /**
     * 轮播图日推
     * @group testRecommendTodayQuery
     * @group article
     */
    public function testRecommendTodayQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Query/recommendTodayQuery.gql');
        $article   = Article::where('type', 'article')->inRandomOrder()->first();
        $variables = [
            'show_time' => '2020-07-29',
            'type'      => 'article',
        ];
        $this->runGuestGQL($query, $variables);

        $article   = Article::where('type', 'recipe')->inRandomOrder()->first();
        $variables = [
            'type' => 'recipe',
        ];
        $this->runGuestGQL($query, $variables);
    }

    /**
     * 创建食谱
     * @group  testCreateArticleMutation
     * @group article
     */
//    public function testCreateArticleMutation()
    //    {
    //        $token   = $this->user->api_token;
    //        $query   = file_get_contents(__DIR__ . '/Article/Mutation/createArticleMutation.gql');
    //        $variables = [
    //            'title' => "测试创建食谱",
    //            'description' => "测试创建食谱",
    //            'type' => 'RECIPE',
    //        ];
    //        $this->runGuestGQL($query,$variables,$this->getRandomUserHeaders());
    //    }

    /**
     * 菜谱打分
     * @group testCreateScoreMutation
     * @group article
     */
    public function testCreateScoreMutation()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Mutation/createScoreMutation.gql');
        $headers   = $this->getRandomUserHeaders();
        $score     = Article::where('type', 'recipe')->inRandomOrder()->first();
        $variables = [
            'scoreable_id'   => $score->id,
            'scoreable_type' => 'articles',
            'score'          => '99',
        ];
        $this->runGuestGQL($query, $variables, $headers);

    }

    /**
     * 删除
     * @group testDeleteArticleMutation
     * @group article
     */
    public function testDeleteArticleMutation()
    {
        $query     = file_get_contents(__DIR__ . '/Article/Mutation/deleteArticleMutation.gql');
        $variables = [
            "id" => $this->article->id,
        ];
        $this->runGuestGQL($query, $variables, $this->getRandomUserHeaders());
    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        $this->article->forceDelete();
        parent::tearDown();
    }

}
