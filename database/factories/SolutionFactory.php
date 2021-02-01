<?php

namespace Database\Factories;

use Haxibiao\Content\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolutionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Solution::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'    => rand(1, 3),

            'issue_id'   => rand(1, 3),

            'answer'     => '问答测试用例'
        ];

    }
}
