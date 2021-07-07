<?php

namespace Haxibiao\Content\Nova\Actions;

use App\EditorChoice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;

class AddMovieToSticks extends Action
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
        $movieIds = $choice->movies()->pluck('movies.id')->toArray();
        $modelIds = [];
        foreach ($models as $movie) {
            $modelIds[] = $movie->id;
        }
        $movieIds = array_merge($movieIds, $modelIds);
        $choice->movies()->sync($movieIds);
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
