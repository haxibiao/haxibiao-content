<?php

namespace Haxibiao\Content\Console;

use App\Image;
use Haxibiao\Content\Collectable;
use Haxibiao\Content\Collection;
use Haxibiao\Content\Post;
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

                if (!$collection) {
                    continue;
                }

                $collectable = Collectable::firstOrNew([
                    'collectable_id'   => $articleCollection->article_id,
                    'collectable_type' => 'articles',
                    'collection_id'    => $articleCollection->collection_id,
                ]);
                $collectable->created_at      = $articleCollection->created_at;
                $collectable->updated_at      = $articleCollection->updated_at;
                $collectable->collection_name = $collection->name;
                $collectable->save(['timestamps' => false]);
            }
            $this->comment('end Fix article_collection');
        }

        // 根据抖音Douyin爬虫检查数据库合集
        $this->handleDouyinMixInfo();

        // 处理合集的关系
        $this->handleCollectionRelationship();
    }

    private function handleDouyinMixInfo()
    {
        Post::with('spider')->publish()->chunk(100, function ($posts) {
            foreach ($posts as $post) {
                $spider = $post->spider;
                if (!$spider) {
                    continue;
                }

                // 获取合集信息
                $mixInfo = data_get($spider, 'data.raw.item_list.0.mix_info');
                if (!$mixInfo) {
                    continue;
                }

                // 当前合集指派给谁(不同的用可以拥有同名的合集)
                $user_id = $post->user_id;
                if (Schema::hasColumn('posts', 'owner_id')) {
                    $owner_id = $post->owner_id;
                    if ($owner_id) {
                        $user_id       = $post->owner_id;
                        $post->user_id = $owner_id;
                        $post->saveDataOnly();
                    }
                }

                // 合集名
                $name = data_get($mixInfo, 'mix_name');
                // 合集logo
                $img = data_get($mixInfo, 'cover_url.url_list.0');
                // 合集描述
                $desc = data_get($mixInfo, 'desc') ?: '暂无描述';

                $collection = \App\Collection::firstOrNew([
                    'name'    => $name,
                    'user_id' => $user_id,
                ]);

                if (!$collection->exists) {
                    if ($img) {
                        $img = Image::saveImage($img);
                    }
                    $collection->forceFill([
                        'description' => $desc,
                        'logo'        => data_get($img, 'path'),
                        'type'        => 'posts',
                        'status'      => Collection::STATUS_ONLINE,
                        'json'        => [
                            'mix_info' => $mixInfo,
                        ],
                    ]);
                    $collection->save();
                }
                $this->info($name);
            }
        });
    }

    private function handleCollectionRelationship()
    {
        Post::with('spider')->publish()->chunk(100, function ($posts) {
            foreach ($posts as $post) {
                $spider = $post->spider;
                if (!$spider) {
                    continue;
                }

                // 获取合集信息
                $mixInfo = data_get($spider, 'data.raw.item_list.0.mix_info');
                if (!$mixInfo) {
                    continue;
                }

                $name           = data_get($mixInfo, 'mix_name');
                $currentEpisode = data_get($mixInfo, 'statis.current_episode');

                $collection = \App\Collection::where('name', $name)
                    ->where('user_id', $post->user_id)
                    ->first();
                if (!$collection) {
                    continue;
                }

                $collection->posts()
                    ->syncWithoutDetaching([
                        $post->id => [
                            'sort_rank' => $currentEpisode,
                        ],
                    ]);

                $this->info($post->id);
            }
        });
    }
}
