<?php

namespace Tests\Feature\GraphQL;

use haxibiao\content\Post;

class PostTest extends GraphQLTestCase
{

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
