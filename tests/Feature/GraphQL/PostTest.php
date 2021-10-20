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
    protected $sharer;
    protected $share;

    public function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->video = Video::factory()->create();

        $this->post  = Post::factory([
            'video_id' => $this->video->id,
        ])->create();

        $this->sharer  = User::factory()->create();
        $this->share = Share::factory()->create([
            'user_id'       => $this->sharer->id,
            'active'        => true,
            'shareable_type'=> $this->post->getMorphClass(),
            'shareable_id'  => $this->post->id,
            'expired_at'    => Carbon::now()->addDay(),
            'uuid'          => Uuid::uuid4()->getHex(),
        ]);
    }

    /**
     * 动态列表
     * @group post
     * @group testPostsQuery
     */
    public function testPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/PostGraphql/postsQuery.graphql');
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
        $query     = file_get_contents(__DIR__ . '/PostGraphql/postWithMoviesQuery.graphql');
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
        $query     = file_get_contents(__DIR__ . '/PostGraphql/userPostsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 浏览记录
     * @group post
     * @group testUserVisitsQuery
     */
    public function testUserVisitsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/PostGraphql/userVisitsQuery.graphql');
        $variables = [
            "user_id" => $this->user->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 浏览记录
     * @group post
     * @group testVisitShareablebyUuid
     */
    public function testVisitShareablebyUuid()
    {   
        $headers = $this->getRandomUserHeaders($this->user);
        $query     = file_get_contents(__DIR__ . '/PostGraphql/VisitShareablebyUuid.graphql');
        $variables = [
            "id" => $this->share->uuid,
        ];
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 推荐视频列表
     * @group post
     * @group testRecommendPostsQuery
     */
    public function testRecommendPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/PostGraphql/recommendPostsQuery.graphql');
        $variables = [];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 视频详情
     * @group post
     * @group testPostQuery
     */
    public function testPostQuery()
    {
        $query = file_get_contents(__DIR__ . '/PostGraphql/postQuery.graphql');
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
        $query     = file_get_contents(__DIR__ . '/PostGraphql/publicPostsQuery.graphql');
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
        $query     = file_get_contents(__DIR__ . '/PostGraphql/postByVidQuery.graphql');
        $headers   = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->video->vid ?? 'v0200f060000bs4pa76ob758ea45jsrg',
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
        $query     = file_get_contents(__DIR__ . '/PostGraphql/shareNewPostQuery.graphql');
        $headers   = [];
        $variables = [
            'id' => $this->post->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 发布动态
     * @group  post
     * @group  testCreatePostContentMutation
     */
    public function testCreatePostContentMutation()
    {
        $token = $this->user->api_token;
        $query   = file_get_contents(__DIR__ . '/PostGraphql/createPostContentMutation.graphql');
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
     * 删除动态
     * @group post
     * @group testDeletePostMutation
     */
    public function testDeletePostMutation()
    {
        $query = file_get_contents(__DIR__ . '/PostGraphql/deletePostMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'id' => $this->post->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
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
        $query   = file_get_contents(__DIR__ . '/PostGraphql/updatePostMutation.graphql');
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

    /**
     * 推荐视频(快速版)
     * @group post
     * @group testFastRecommendPostsQuery
     */
    public function testFastRecommendPostsQuery()
    {
        $query = file_get_contents(__DIR__ .'/PostGraphql/fastRecommendPostsQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query,[],$headers);

        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query,[],[]);
    }


    protected function tearDown(): void
    {
        $this->video->forceDelete();
        $this->post->forceDelete();
        $this->user->forceDelete();
        parent::tearDown();
    }

}
