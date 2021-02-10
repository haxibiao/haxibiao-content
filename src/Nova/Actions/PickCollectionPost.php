<?php

namespace Haxibiao\Content\Nova\Actions;

use App\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Techouse\SelectAutoComplete\SelectAutoComplete;

class PickCollectionPost extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = '加入合集';

    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $fields
     * @param \Illuminate\Support\Collection $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {

        $collectableIds = $models->pluck('id');
        $collection     = \App\Collection::find($fields->collection);
        foreach ($collectableIds as $collectableId) {

            $post   = Post::find($collectableId);
            $spider = $post->spider;
            if (!$spider) {
                $sortRank = $collection->posts()->count();
            } else {
                $mixInfo  = data_get($spider, 'data.raw.item_list.0.mix_info');
                $sortRank = data_get($mixInfo, 'statis.current_episode', 0);
            }

            $collection->posts()
                ->syncWithoutDetaching([
                    $post->id => [
                        'sort_rank' => $sortRank,
                    ],
                ]);
        }
        $collection->updateCountPosts();

        return Action::message('加入成功');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        $data = \App\Collection::query()
            ->orderBy('count', 'DESC')
            ->pluck('name', 'id')
            ->toArray();
        return [
            SelectAutoComplete::make(_("加入合集"), 'collection')->options(
                $data
            ),
        ];
    }
}
