<?php

namespace Haxibiao\Content\Console;

use Haxibiao\Content\Categorized;
use Haxibiao\Content\Collectable;
use Haxibiao\Content\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CollectionReFactoringCommand extends Command
{
    protected $signature = 'haxibiao:collection:refactoring';

    protected $description = '重新构建collection,可重复执行';

    public function handle()
    {
        // article_collection
        if (Schema::hasTable('article_collection')) {
            $this->comment('start Fix article_collection');
            $articleCollections = DB::table('article_collection')->get();
            foreach ($articleCollections as $articleCollection) {
                $collection = Collection::find($articleCollection->collection_id);

                if(!$collection){
                    continue;
                }

                $collectable = Collectable::firstOrNew([
                    'collectable_id'   => $articleCollection->article_id,
                    'collectable_type' => 'articles',
                    'collection_id'      => $articleCollection->collection_id,
                ]);
                $collectable->created_at = $articleCollection->created_at;
                $collectable->updated_at = $articleCollection->updated_at;
                $collectable->collection_name = $collection->name;
                $collectable->save(['timestamps' => false]);
            }
            $this->comment('end Fix article_collection');
        }
    }

}
