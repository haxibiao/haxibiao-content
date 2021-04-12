<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 专题大全页
     * @group category
     */
    public function testCategoryIndex()
    {
        $response = $this->get("/category");
        $response->assertStatus(200);
    }

    /**
     * 专题页
     * @group category
     */
    public function testCategoryPage()
    {
        $response = $this->get("/category/" . Category::max('id'));
        $response->assertStatus(200);
    }

}
