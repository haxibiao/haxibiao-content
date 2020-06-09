<?php

namespace Tests\Feature\GraphQL;

use haxibiao\content\Post;

class PostTest extends GraphQLTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Post::firstOrCreate([
            'user_id' => rand(1, 10),
            'content' => "测试动态",
            'status'  => Post::PUBLISH_STATUS,
        ]);

        Post::firstOrCreate([
            'user_id'  => rand(1, 10),
            'video_id' => rand(1, 10),
            'content'  => "有视频的动态",
            'status'   => Post::PUBLISH_STATUS,
        ]);

    }

    //用户发布视频动态
    public function testUserPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/UserPostsQuery.gql');
        $variables = [
            "user_id" => 4,
        ];
        $this->runGQL($query, $variables);
    }

    public function testRecommendPostsQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/RecommendPostsQuery.gql');
        $variables = [];
        $this->runGQL($query, $variables);
    }

    // 视频详情
    public function testPostQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Post/PostQuery.gql');
        $id        = Post::first()->id;
        $variables = [
            'id' => $id,
        ];
        $this->runGQL($query, $variables);
    }

}
