<?php
namespace Database\Factories;

use Haxibiao\Content\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => rand(1, 3),
            'title'   => 'A test article title',
            'body'    => 'A test article body ....',
        ];
    }
}
