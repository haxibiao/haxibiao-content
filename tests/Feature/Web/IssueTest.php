<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IssueTest extends TestCase
{

    use DatabaseTransactions;

    public function testCategories()
    {
        $response = $this->get('/categories-for-question');
        $response->assertStatus(200);
    }

    public function testBonused()
    {
        $response = $this->get('/question-bonused');
        $response->assertStatus(200);
    }

    public function testAdd()
    {
        $question    = \App\Question::inRandomOrder()->first();
        $question_id = $question->id;
        $answer      = $question->answer;
        $response    = $this->post('/question-add', ["question_id" => $question_id, "answer" => $answer]);
        $response->assertStatus(302);
    }

    public function testIndex()
    {
        $response = $this->get("/question");
        $response->assertStatus(200);
    }

    public function testStore()
    {
        $user     = \App\User::inRandomOrder()->first();
        $response = $this->actingAs($user)->post("/question");
        $response->assertStatus(302);
    }

    public function testShow()
    {
        $id       = \App\Issue::inRandomOrder()->first()->id;
        $response = $this->get("/question/{$id}");
        $response->assertStatus(200);
    }

    public function testDestroy()
    {
        $id       = \App\Issue::inRandomOrder()->first()->id;
        $response = $this->delete("/question/{$id}");
        $response->assertStatus(302);
    }

    public function testSolution()
    {
        $user         = \App\User::inRandomOrder()->first();
        $solution     = \App\Solution::inRandomOrder()->first();
        $solution->id = null;
        $date         = $solution->toArray();
        $response     = $this->actingAs($user)->post("/answer", $date);
        $response->assertStatus(302);
    }
}
