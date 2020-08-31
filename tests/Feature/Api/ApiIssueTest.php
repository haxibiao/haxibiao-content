<?php

namespace Haxibiao\Content\Tests\Feature\Api;


use App\Issue;
use App\Solution;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiIssueTest extends TestCase
{
    use DatabaseTransactions;

    public function testSuggest()
    {
        $response = $this->get("/api/suggest-question");
        $response->assertStatus(200);
    }

    public function testQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $response = $this->get("/api/question/{$id}");
        $response->assertStatus(200);
    }

    public function testReportQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $response = $this->get("/api/report-question-{$id}");
        $response->assertStatus(200);
    }


    public function testFavoriteQuestion()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/api/favorite-question-{$id}");
        $response->assertStatus(200);
    }

    public function testAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $response = $this->get("/api/answer/{$id}");
        $response->assertStatus(200);
    }

    public function testLikeAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $response = $this->get("/api/like-answer-{$id}");
        $response->assertStatus(200);
    }

    public function testUnlikeAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $response = $this->get("/api/unlike-answer-{$id}");
        $response->assertStatus(200);
    }

    public function testReportAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $response = $this->get("/api/report-answer-{$id}");
        $response->assertStatus(200);
    }

    public function testDeleteAnswer()
    {
        $id = Solution::inRandomOrder()->first()->id;
        $response = $this->get("/api/delete-answer-{$id}");
        $response->assertStatus(200);
    }


    public function testQuestionUninvited()
    {

        $issue_id = Issue::inRandomOrder()->first()->id;
        $user = User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/api/question-{$issue_id}-uninvited");
        $response->assertStatus(200);
    }


    public function testQuestionInvite()
    {
        $qid = Issue::inRandomOrder()->first()->id;
        $invite_uid = \App\User::inRandomOrder()->first()->id;
        $user = \App\User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/api/question-{$qid}-invite-user-{$invite_uid}");
        $response->assertStatus(201);
    }


    public function testAnswered()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $response = $this->post("/api/question-{$id}-answered");
        $response->assertStatus(200);
    }


    public function testDelete()
    {
        $id = Issue::inRandomOrder()->first()->id;
        $response = $this->get("/api/delete-question-{$id}");
        $response->assertStatus(200);
    }

    public function testCommend()
    {
        $response = $this->get("/api/commend-question");
        $response->assertStatus(200);
    }
}