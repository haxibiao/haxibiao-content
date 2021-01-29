<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Issue;
use App\Solution;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SolutionTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $issue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->getRandomUser();
        $this->issue = Issue::inRandomOrder()->first();

    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Mutation ----------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * @group solution
     * @group testAddSolutionMutation
     */
    public function testAddSolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/addSolutionMutation.gql');

        $headers = $this->getRandomUserHeaders();

        $variables = [
            'issue_id' => $this->issue->id,
            'answer'   => "test hello world",
        ];

        $this->runGuestGQL($query, $variables, $headers);

        $variables = [
            'issue_id'   => $this->issue->id,
            'answer'     => "test hello world",
            'image_urls' => ['http://cos.dongdianyi.com/storage/img/159.jpg'],
        ];
        $this->runGuestGQL($query, $variables, $headers);

    }
    /**
     * @group solution
     * @group testDeleteSolutionMutation
     */
    public function testDeleteSolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/deleteSolutionMutation.gql');
        $user  = User::inRandomorder()->first();
        $token = $user->api_token;

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $args  = [
            'user_id'  => $user->id,
            'issue_id' => $this->issue->id,
            'answer'   => "i'm a solution of issue",
        ];
        //用参数创建一个回答
        $solution  = Solution::firstOrCreate($args);
        $variables = [
            'id' => $solution->id,
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Query ----------------------------- */
    /* ----------------------------up----------------------------------------- */
    /**
     * @group solution
     * @group testSolutionsQuery
     */
    public function testSolutionsQuery()
    {

        $query = file_get_contents(__DIR__ . '/Solution/solutionsQuery.gql');

        $variables = [
            'issue_id' => $this->issue->id,
            'count'    => 3
        ];
        $this->runGuestGQL($query, $variables);

    }
    /**
     * @group solution
     * @group testSolutionQuery
     */
    public function testSolutionQuery()
    {

        $query = file_get_contents(__DIR__ . '/Solution/solutionQuery.gql');

        $solution = Solution::inRandomorder()->first();

        $variables = [
            'id' => $solution->id,
        ];
        $this->runGuestGQL($query, $variables);

    }

    /**
     * @group solution
     * @group testMySolutionMutation
     */
    public function testMySolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/mySolutionsQuery.gql');

        $token = $this->user->api_token;

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $variables = [
            'user_id' => $this->user->id,
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }
    
    protected function tearDown(): void
    {
        $this->user->forceDelete();

        parent::tearDown();
    }
}
