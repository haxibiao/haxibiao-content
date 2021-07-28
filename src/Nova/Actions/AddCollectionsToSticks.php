<?php

namespace Haxibiao\Content\Nova\Actions;

use App\EditorChoice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;

class AddCollectionsToSticks extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = '添加到精选';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $choice = EditorChoice::where('title', $fields->title)->first();
        if (!$choice) {
            return Action::danger("请先创建小编精选!");
        }
        $collection_ids = $choice->collections()->pluck('activities.id')->toArray();
        $modelIds       = [];
        foreach ($models as $collection) {
            $modelIds[] = $collection->id;
        }
        $collection_ids = array_merge($collection_ids, $modelIds);
        $choice->collections()->sync($collection_ids);
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make('精选名称', 'title')->suggestions(EditorChoice::pluck('title')->toArray()),
        ];
    }
}
