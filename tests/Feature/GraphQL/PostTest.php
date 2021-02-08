<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Post;
use App\Share;
use App\User;
use App\Video;
use Carbon\Carbon;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;

class PostTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $post;
    protected $video;

    public function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->video = Video::factory()->create();
        $this->post  = Post::factory([
            'video_id' => $this->video->id,
        ])->create();
    }

    /**
     * 动态列表
     * @group post
     * @group testPostsQuery
     */
    public function testPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/postsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 动态列表
     * @group post
     * @group testPostWithMoviesQuery
     */
    public function testPostWithMoviesQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/postWithMoviesQuery.graphql');

        // 未登陆
        $variables = [];
        $this->startGraphQL($query, $variables,[]);

        // 登陆
        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 推荐视频
     * @group post
     * @group testPublicVideosQuery
     */
    public function testPublicVideosQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/publicVideosQuery.graphql');

        // 未登陆
        $variables = [];
        $this->startGraphQL($query, $variables,[]);

        // 登陆
        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 用户发布视频动态
     * @group post
     * @group testUserPostsQuery
     */
    public function testUserPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/userPostsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 浏览记录
     *
     * @group post
     * @group testUserVisitsQuery
     */
    public function testUserVisitsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/userVisitsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 浏览记录
     *
     * @group post
     * @group testVisitShareablebyUuid
     */
    public function testVisitShareablebyUuid()
    {
        $sharer  = User::factory()->create();
        $headers = $this->getRandomUserHeaders($this->user);
        $post   = Post::factory()->create();

        $share = Share::factory()->create([
            'user_id'       => $sharer->id,
            'active'        => true,
            'shareable_type'=> $post->getMorphClass(),
            'shareable_id'  => $post->id,
            'expired_at'    => Carbon::now()->addDay(),
            'uuid'          => Uuid::uuid4()->getHex(),
        ]);

        $query     = file_get_contents(__DIR__ . '/Post/VisitShareablebyUuid.graphql');
        $variables = [
            "uuid" => $share->uuid,
        ];
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 推荐视频列表
     *
     * @group post
     * @group testRecommendPostsQuery
     */
    public function testRecommendPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/recommendPostsQuery.graphql');
        $variables = [];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 视频详情
     *
     * @group post
     * @group testPostQuery
     */
    public function testPostQuery()
    {
        $query = file_get_contents(__DIR__ . '/Post/postQuery.graphql');

        $variables = [
            'id' => $this->post->id,
        ];

        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  post
     * @group  testPublicPostsQuery
     */
    public function testPublicPostsQuery()
    {
        $user      = $this->user;
        $query     = file_get_contents(__DIR__ . '/Post/publicPostsQuery.graphql');
        $headers   = [];
        $variables = [
            'user_id' => $user->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group  post
     * @group  testPostByVidQuery
     */
    public function testPostByVidQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/postByVidQuery.graphql');
        $headers   = [];
        $variables = [
            'vid' => $this->video->vid ?: 'v0200f060000bs4pa76ob758ea45jsrg',
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group  post
     * @group  testShareNewPostQuery
     */
    public function testShareNewPostQuery()
    {
        $post      = $this->post;
        $query     = file_get_contents(__DIR__ . '/Post/shareNewPostQuery.graphql');
        $headers   = [];
        $variables = [
            'id' => $this->post->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * ========================================================================
     * ============================Mutation=======================++===========
     * ========================================================================
     *  */

    /**
     * @group  post
     * @group  createSeekMovieMutation
     */
    public function testCreateSeekMovieMutation()
    {
        $query   = file_get_contents(__DIR__ . '/Post/createSeekMovieMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'name'          => '独孤九剑',
            'description'   => '求高清资源',
            // 注释的原因：GQL后台测试正常，跑UT就报错
            //'images' => [$this->getBase64ImageString()]
        ];
        $this->startGraphQL($query, $variables, $headers);
    }
    /**
     * @group  post
     * @group  testCreatePostContentMutation
     */
    public function testCreatePostContentMutation()
    {
        $token = $this->user->api_token;
        $query   = file_get_contents(__DIR__ . '/Post/createPostContentMutation.graphql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];

        $image  = UploadedFile::fake()->image('photo.jpg');
        $base64 = 'data:' . $image->getMimeType() . ';base64,' . base64_encode(file_get_contents($image->getRealPath()));

        $video = $this->video;

        //情形1:创建视频动态
        $variables = [
            'video_id' => $video->id,
            'body'     => '测试创建创建视频动态',
        ];
        $this->startGraphQL($query, $variables, $headers);

         //情形2:创建带图动态
         $variables = [
             'images'       => [$base64],
             'body'         => '测试创建带图动态?',
             'type'         => 'POST',
             'category_ids' => [1],
         ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 抖音解析接口
     * @group  post
     * @group  testResolveDouyinVideo
     */
    public function testResolveDouyinVideo()
    {
        $user = $this->user;
        //确保后面UT不重复
        $headers      = $this->getRandomUserHeaders($user);
        $query     = file_get_contents(__DIR__ . '/Post/resolveDouyinVideoMutation.graphql');
        $variables = [
            'share_link' => "#在抖音，记录美好生活#美元如何全球褥羊毛？经济危机下，2万亿救市的深层动力，你怎么看？#经济 #教育#云上大课堂 #抖音小助手 https://v.douyin.com/vruTta/ 复制此链接，打开【抖音短视频】，直接观看视频！",
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 编辑动态
     * @group  post
     * @group  testUpdatePostMutation
     */
    public function testUpdatePostMutation()
    {
        $token   = $this->user->api_token;
        $post    = $this->post;
        $query   = file_get_contents(__DIR__ . '/Post/updatePostMutation.graphql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        $variables = [
            'id'          => $post->id,
            'content'     => '测试',
            'description' => '测试',
            'tag_names'   => ['测试', '测试2', '测试2'],
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        $this->video->forceDelete();
        $this->post->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }

}
