<?php

namespace Haxibiao\Content\Tests\Feature\GraphQL;

use App\Spider;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Haxibiao\Content\Post;
use Haxibiao\Media\Video;
use Illuminate\Http\UploadedFile;

class PostTest extends GraphQLTestCase
{

    protected $user;

    protected $post;

    protected $video;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('id', '<', 100)->inRandomOrder()->first();
        $this->post = Post::where('id', '<', 100)->inRandomOrder()->first();
        $this->video = Video::where('id', '<', 100)->inRandomOrder()->first();
    }

    /**
     * 用户发布视频动态
     *
     * @group post
     * @group testUserPostsQuery
     */
    public function testUserPostsQuery()
    {
        $query = file_get_contents(__DIR__ . '/post/postsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->runGQL($query, $variables);
    }

    /**
     * 推荐视频列表
     *
     * @group post
     * @group testRecommendPostsQuery
     */
    public function testRecommendPostsQuery()
    {
        $query = file_get_contents(__DIR__ . '/post/recommendPostsQuery.graphql');
        $variables = [];
        $this->runGQL($query, $variables);
    }

    /**
     * 视频详情
     *
     * @group post
     * @group testPostQuery
     */
    public function testPostQuery()
    {
        $query = file_get_contents(__DIR__ . '/post/postQuery.graphql');

        $variables = [
            'id' => $this->post->id,
        ];

        $this->runGQL($query, $variables);
    }

    /**
     * @group  post
     * @group  testPublicPostsQuery
     */
    public function testPublicPostsQuery()
    {
        $user = User::find(1);
        $query = file_get_contents(__DIR__ . '/post/publicPostsQuery.graphql');
        $headers = [];
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
        $query = file_get_contents(__DIR__ . '/post/postByVidQuery.graphql');
        $headers = [];
        $variables = [
            'vid' => $this->video->vid?:'v0200f060000bs4pa76ob758ea45jsrg',
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group  post
     * @group  testShareNewPostQuery
     */
    public function testShareNewPostQuery()
    {
        $post = Post::find(2);
        $query = file_get_contents(__DIR__ . '/post/shareNewPostQuery.graphql');
        $headers = [];
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
     * @group  testCreatePostContentMutation
     */
    public function testCreatePostContentMutation()
    {
        $token = User::find(1)->api_token;
        $query = file_get_contents(__DIR__ . '/post/createPostContentMutation.graphql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $image = UploadedFile::fake()->image('photo.jpg');
        $base64 = 'data:' . $image->getMimeType() . ';base64,' . base64_encode(file_get_contents($image->getRealPath()));

        $post = Post::has('video')->first();
        $video = $post->video;

        //情形1:创建视频动态
        $variables = [
            'video_id' => $video->id,
            'body' => '测试创建创建视频动态',
            'type' => 'POST',
        ];
        $this->startGraphQL($query, $variables, $headers);

        //情形2:创建带图动态
        $variables = [
            'images' => [$base64],
            'body' => '测试创建带图动态?',
            'type' => 'POST',
            'category_ids' => [2],
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * @group  post
     * @group  testResolveDouyinVideo
     */
    public function testResolveDouyinVideo()
    {
        //确保后面UT不重复
        Spider::where('source_url', 'https://v.douyin.com/vruTta/')->delete();
        $user = User::where("ticket", ">", 10)->first();
        $query = file_get_contents(__DIR__ . '/post/resolveDouyinVideoMutation.graphql');
        $variables = [
            'share_link' => "#在抖音，记录美好生活#美元如何全球褥羊毛？经济危机下，2万亿救市的深层动力，你怎么看？#经济 #教育#云上大课堂 #抖音小助手 https://v.douyin.com/vruTta/ 复制此链接，打开【抖音短视频】，直接观看视频！",
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $user->api_token,
            'Accept' => 'application/json',
        ];

        $this->runGuestGQL($query, $variables, $headers);
    }

    /**
     * @group  post
     * @group  testUpdatePostMutation
     */
    public function testUpdatePostMutation()
    {
        $token = User::find(1)->api_token;
        $post = Post::find(2);
        info($post->user_id);
        $query = file_get_contents(__DIR__ . '/post/UpdatePostMutation.graphql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
        $variables = [
            'id' => $post->id,
            'content' => '测试',
            'description' => '测试',
            'tag_names' => ['测试', '测试', '测试'],
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    //todo MakePostByMovie方法的逻辑还没写完

    // /**
    //  * @group  post
    //  * @group  testMakePostByMovie
    //  */
    // public function testMakePostByMovie()
    // {
    //     $token = User::find(1)->api_token;
    //     $post = Post::find(2);
    //     $query = file_get_contents(__DIR__ . '/post/makePostByMovieMutation.graphql');
    //     $headers = [
    //         'Authorization' => 'Bearer ' . $token,
    //         'Accept' => 'application/json',
    //     ];
    //     $variables = [
    //     ];
    //     $this->startGraphQL($query, $variables, $headers);
    // }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

}
