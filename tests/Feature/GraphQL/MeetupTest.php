<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\User;
use App\Article;
use ArithmeticError;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MeetupTest extends GraphQLTestCase
{
    use DatabaseTransactions;
    protected $user;
    protected $staff;
    protected $admin;
    protected $meetup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory([
            'name'      => '匿名',
            'phone'     => '13956987412',
            'password'  => 'password',
        ])->create();

        $this->staff = User::factory([
            'name'      => '匿名',
            'phone'     => '13956987410',
            'password'  => 'staff',
            'role_id'   => User::STAFF_ROLE,
        ])->create();

        $this->admin = User::factory([
            'name'      => '匿名',
            'phone'     => '13956987413',
            'password'  => 'admin',
            'role_id'   => User::ADMIN_STATUS,
        ])->create();

        $this->meetup = Article::create([
            'title'         => '1111',
            'description'   => '2222',
            'images'        => ['https://bilibili.renzaichazai.cn/moviecloud/app/image/67497.jpg'],
            'expires_at'    => date('Y-m-d h:i:s', strtotime('+1 day')),
            'user_id'       => $this->user->id,
        ]);
    }

    /**
     * 创建约单
     * @group meetup
     * @group testCreateMeetupMutation
     */
    public function testCreateMeetupMutation()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/createMeetupMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->admin);
        $variables = [
            'title'         => '1111',
            'description'   => '2222',
            'images'        => ['https://bilibili.renzaichazai.cn/moviecloud/app/image/67497.jpg'],
            'expires_at'    => date('Y-m-d h:i:s', strtotime('+1 day')),
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 约单列表
     * @group meetup
     * @group testMeetupsQuery
     */
    public function testMeetupsQuery()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/meetupsQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'user_id' => $this->user->id,
            'filter'  => 'HOT',
        ];
        $this->startGraphQL($query,$variables,$headers);

        $variables = [
            'user_id' => $this->user->id,
            'filter'  => 'RANDOM',
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 待审批“加盟通知”的数量
     * @group meetup
     * @group testPendingApprovalAmountQuery
     */
    public function testPendingApprovalAmountQuery()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/pendingApprovalAmountQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 查询我发起/加入的联盟列表
     * @group meetup
     * @group testJoinedLeagueOfMeetupQuery
     */
    public function testJoinedLeagueOfMeetupQuery()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/joinedLeagueOfMeetupQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'filter'    => 'INITIATOR',
        ];
        $this->startGraphQL($query,$variables,$headers);

        $variables = [
            'filter'    => 'PARTICIPATOR',
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * @group meetup
     * @group testJoinedLeagueOfMeetupQuery
     */
    public function testJoinedMeetupsQuery()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/joinedMeetupsQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'status' => 'REGISTERING',
        ];
        $this->startGraphQL($query,$variables,$headers);

        $variables = [
            'status' => 'REGISTERED',
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 报名/加入约单
     * @group meetup
     * @group testJoinMeetupMutation
     */
    public function testJoinMeetupMutation()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/joinMeetupMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => Article::where('type',Article::MEETUP)->pluck('id')->first(),
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 联盟列表
     * @group meetup
     * @group testLeagueListQuery
     */
    public function testLeagueListQuery()
    {
        $query = file_get_contents(__DIR__ .'/Meetup/leagueListQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'filter' => 'ALL',
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 修改约单
     * @group meetup
     * @group testUpdateMeetupMutation
     */
    public function testUpdateMeetupMutation()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/updateMeetupMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->meetup->id,
            'images' => ['https://bilibili.renzaichazai.cn/moviecloud/app/image/1.jpg'],
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 约单详情
     * @group meetup
     * @group testMeetupQuery
     */
    public function testMeetupQuery()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/meetupQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->meetup->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 删除约单
     * @group meetup
     * @group testDeleteMeetupMutation
     */
    public function testDeleteMeetupMutation()
    {
        $query = file_get_contents(__DIR__ . '/Meetup/deleteMeetupMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->meetup->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }
}
