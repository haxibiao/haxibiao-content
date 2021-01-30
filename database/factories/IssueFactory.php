<?php

namespace Database\Factories;

use Haxibiao\Content\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Issue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // 作者
            'user_id'    => rand(1, 3),

            // 提问
            'title'      => '测试问答',

            // 描述
            'background' => '测试的问答描述',
        ];

    }
}
