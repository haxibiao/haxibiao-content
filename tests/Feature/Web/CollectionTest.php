<?php

namespace Haxibiao\Content\Tests\Feature\Web;

use App\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 访问合集主页
     * @group collection
     */
    public function testCollectionPage()
    {
        $max_collection_id = Collection::max('id');
        $response          = $this->get("/collection/" . $max_collection_id);
        $response->assertStatus(200);
    }

}
