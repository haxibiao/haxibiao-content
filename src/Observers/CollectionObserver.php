<?php

namespace Haxibiao\Content\Observers;

use Haxibiao\Content\Collection;

class CollectionObserver
{
    public function creating(Collection $collection)
    {
        // 完善GQL属性验证非空counts
        $collection->count_articles = 0;
        $collection->count_posts    = 0;
        $collection->count_follows  = 0;
    }
}
