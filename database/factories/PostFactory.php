<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // 作者
            'user_id'     => rand(1, 3),

            // 视频ID
            'video_id'    => null,

            // 描述
            'description' => '测试的动态的配文',

            // 内容
            'content'     => '测试动态的长篇正文',

            // 状态
            'status'      => \App\Post::PUBLISH_STATUS,
        ];

    }
}
