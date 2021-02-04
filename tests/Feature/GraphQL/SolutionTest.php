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
    protected $solver;
    protected $issue;
    protected $solution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->solver  = User::factory()->create();

        $this->issue = Issue::factory([
            'user_id'    => $this->user->id,
            'title'      => '测试问答',
            'background' => '测试的问答描述',
        ])->create();
        $this->solution = Solution::factory([
            'user_id'  => $this->solver->id,
            'issue_id' => $this->issue->id,
            'answer'   => '问答测试用例',
        ])->create();
    }

    /**
     * 回复问答
     * @group solution
     * @group testAddSolutionMutation
     */
    public function testAddSolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/addSolutionMutation.gql');

        $headers = $this->getRandomUserHeaders($this->user);

        // 不带图
        $variables = [
            'issue_id' => $this->issue->id,
            'answer'   => "test hello world",
        ];
        $this->startGraphQL($query, $variables, $headers);

        // 带图
        $variables = [
            'issue_id'   => $this->issue->id,
            'answer'     => "test hello world",
            'image_urls' => ['http://cos.dongdianyi.com/storage/img/159.jpg'],
        ];
        $this->startGraphQL($query, $variables, $headers);

    }
    /**
     * 删除回答
     * @group solution
     * @group testDeleteSolutionMutation
     */
    public function testDeleteSolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/deleteSolutionMutation.gql');

        $headers = $this->getRandomUserHeaders($this->solver);
        $variables = [
            'id' => $this->solution->id,
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 回复查询
     * @group solution
     * @group testSolutionsQuery
     */
    public function testSolutionsQuery()
    {

        $query = file_get_contents(__DIR__ . '/Solution/solutionsQuery.gql');

        $variables = [
            'issue_id' => $this->issue->id,
            'count'    => 3,
        ];
        $this->startGraphQL($query, $variables);

    }
    /**
     * 回复详情
     * @group solution
     * @group testSolutionQuery
     */
    public function testSolutionQuery()
    {

        $query = file_get_contents(__DIR__ . '/Solution/solutionQuery.gql');

        $variables = [
            'id' => $this->solution->id,
        ];
        $this->startGraphQL($query, $variables);

    }

    /**
     * 查询我的回答
     * @group solution
     * @group testMySolutionMutation
     */
    public function testMySolutionMutation()
    {
        $query = file_get_contents(__DIR__ . '/Solution/mySolutionsQuery.gql');
        $solver  = $this->solver;
        $headers = $this->getRandomUserHeaders($solver);

        $variables = [
            'user_id' => $solver->id,
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        $this->solution->forceDelete();
        $this->issue->forceDelete();
        $this->user->forceDelete();
        $this->solver->forceDelete();
        parent::tearDown();
    }
}
