<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
    }

    /**
     * 合集大全主页
     * @group collection
     */
    public function testCollectionWeb()
    {
        $response = $this->get("/collection");
        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
