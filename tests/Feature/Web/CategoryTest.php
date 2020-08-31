<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Category;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testCategoryList()
    {
        $category = \App\Category::find(2);
        $user = User::where('role_id', User::ADMIN_STATUS)->first();
        $response = $this->actingAs($user)->json('GET', '/category/list');
        $response->assertStatus(200);
    }

    public function testIndex()
    {
        $response = $this->get("/category");
        $response->assertStatus(200);
    }

    public function testCreate()
    {
        $user = User::inRandomOrder()->first();
        $response = $this->actingAs($user)->get("/category/create");
        $response->assertStatus(200);
    }

    public function testStore()
    {
        $user = User::inRandomOrder()->first();
        $name = "testName";
        $name_en = "testNameEn";
        $description = "testDescription";
        $response = $this->actingAs($user)->call('POST', "/category", [
            'name' => $name, 'name_en' => $name_en, 'description' => $description]);
        $response->assertStatus(302);
    }

    public function testShow()
    {
        $id = Category::inRandomOrder()->first()->id;
        $response = $this->get("/category/{$id}");
        $response->assertStatus(200);
    }

    public function testEdit()
    {
        $user = User::inRandomOrder()->first();
        $category = Category::inRandomOrder()->first();
        $id = $category->id;
        $response = $this->actingAs($user)->get("/category/{$id}/edit");
        $response->assertStatus(!canEdit($category) ? 403 : 200);
    }

    public function testUpdate()
    {
        $user = User::inRandomOrder()->first();
        $category = Category::inRandomOrder()->first();
        $id = $category->id;
        $response = $this->actingAs($user)->put("/category/{$id}");
        $response->assertStatus(!canEdit($category) ? 403 : 302);
    }

    public function testDestroy()
    {
        $user = User::inRandomOrder()->first();
        $category = Category::inRandomOrder()->first();
        $id = $category->id;
        $response = $this->actingAs($user)->delete("/category/{$id}");
        $response->assertStatus(!canEdit($category) ? 403 : 200);
    }
}