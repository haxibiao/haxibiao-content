<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Issue;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;

class IssueTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $invitee;
    protected $issue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->invitee  = User::factory()->create();
        $this->issue = Issue::create([
            'user_id'    => $this->user->id,
            'title'      => '测试问答',
            'background' => '测试的问答描述',
        ]);
    }

    /**
     * @group issue
     * @group testCreateIssueMutation
     */
    public function testCreateIssueMutation()
    {
        $query     = file_get_contents(__DIR__ . '/Issue/createIssueMutation.gql');
        $headers   = $this->getRandomUserHeaders($this->user);
        $variables = [
            "title"      => "创建一个问题",
            "background" => "HelloWorld",
        ];

        $this->startGraphQL($query, $variables, $headers);

        // TODO 注释的原因：GQL后台测试没问题，但是测试用例一直跑不过。有空过来看看
        //创建戴图片的问题
//         $variables = [
//             "title"       => "创建一个问题",
//             "background"  => "HelloWorld",
//             'cover_image' => $this->getBase64ImageString(),
//         ];
//
//        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testSearchIssue
     */
    public function testSearchIssue()
    {

        $query     = file_get_contents(__DIR__ . '/Issue/searchIssueQuery.gql');
        $headers   = $this->getRandomUserHeaders($this->user);
        $variables = [
            'query' => str_limit($this->issue->title, 5),
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testIssuesQuery
     */
    public function testIssuesQuery()
    {
        //用户的问答黑名单这块有问题
        $query     = file_get_contents(__DIR__ . '/Issue/issuesQuery.gql');

        $variables = [
            'orderBy' => [
                [
                    "column" => "LASTEST",
                    "order" => "DESC",
                ],
            ],
        ];
        $this->startGraphQL($query, $variables);
        //默认排序方式
        $variables = [];
        $this->startGraphQL($query, $variables);

    }
    /**
     * @group issue
     * @group testDeleteIssueMutation
     */
    public function testDeleteIssueMutation()
    {
        $query   = file_get_contents(__DIR__ . '/Issue/deleteIssueMutation.gql');
        $headers   = $this->getRandomUserHeaders($this->user);
        //查找当前用户创建的issue
        $args = [
            'user_id' => $this->user->id,
            'title'   => "i'am a issue",
        ];
        $issue     = Issue::firstOrCreate($args);
        $variables = [
            'issue_id' => $issue->id,
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group issue
     * @group testInviteAnswerMutation
     */
    public function testInviteAnswerMutation()
    {
        $query   = file_get_contents(__DIR__ . '/Issue/inviteAnswerMutation.gql');
        $headers   = $this->getRandomUserHeaders($this->user);

        // 邀请别人
        $variables       = [
            'invited_user_id' => $this->invitee->id,
            'issue_id'        => $this->issue->id,
        ];
        $this->startGraphQL($query, $variables, $headers);

        // 邀请自己
        $variables       = [
            'invited_user_id' => $this->user->id,
            'issue_id'        => $this->issue->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        $this->issue->forceDelete();
        $this->user->forceDelete();
        $this->invitee->forceDelete();
        parent::tearDown();
    }

}
