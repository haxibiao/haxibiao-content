<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
    }

    /**
     * 专题大全页
     * @group category
     */
    public function testCategoryWeb()
    {
        $response = $this->get("/category");
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
