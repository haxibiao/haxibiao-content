<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Issue;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IssueTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $issue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::inRandomorder()->first();
        $this->issue = Issue::inRandomorder()->first();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    /**
     * @group issue
     * @group testCreateIssueMutation
     */
    public function testCreateIssueMutation()
    {
        $query     = file_get_contents(__DIR__ . '/Issue/createIssueMutation.gql');
        $base64    = $this->getBase64ImageString();
        $headers   = $this->getRandomUserHeaders();
        $variables = [
            "title"      => "创建一个问题",
            "background" => "HelloWorld",
        ];

        $this->runGuestGQL($query, $variables, $headers);

        //创建戴图片的问题
        $variables = [
            "title"       => "创建一个问题",
            "background"  => "HelloWorld",
            'cover_image' => $base64,
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testSearchIssue
     */
    public function testSearchIssue()
    {

        $query     = file_get_contents(__DIR__ . '/Issue/searchIssueQuery.gql');
        $headers   = $this->getRandomUserHeaders();
        $variables = [
            'query' => str_limit($this->issue->title, 5),
        ];
        $this->runGuestGQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testIssuesQuery
     */
    public function testIssuesQuery()
    {

        $query = file_get_contents(__DIR__ . '/Issue/issuesQuery.gql');
        $variables = [
            'orderBy' => [
                [
                    "order" => "DESC",
                    "field" => "HOT",
                ],
            ],
        ];
        $this->runGuestGQL($query, $variables);
        //默认排序方式
        $variables = [];
        $this->runGuestGQL($query, $variables);

    }
    /**
     * @group issue
     * @group testDeleteIssueMutation
     */
    public function testDeleteIssueMutation()
    {
        $query   = file_get_contents(__DIR__ . '/Issue/deleteIssueMutation.gql');
        $token   = $this->user->api_token;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        //查找当前用户创建的issue
        $args = [
            'user_id' => $this->user->id,
            'title'   => "i'am a issue",
        ];
        $issue     = Issue::firstOrCreate($args);
        $variables = [
            'issue_id' => $issue->id,
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testInviteAnswerMutation
     */
    public function testInviteAnswerMutation()
    {
        $query   = file_get_contents(__DIR__ . '/Issue/inviteAnswerMutation.gql');
        $token   = $this->user->api_token;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $invited_user_id = User::inRandomorder()->first()->id;
        $variables = [
            'invited_user_id'=>$invited_user_id, 
            'issue_id' => $this->issue->id
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }

}
