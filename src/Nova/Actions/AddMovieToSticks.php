<?php

namespace Haxibiao\Content\Nova\Actions;

use App\EditorChoice;
use App\Site;
use App\Stick;
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
        $user   = \Auth::user();
        $choice = EditorChoice::where('title', $fields->title)->first();
        $site   = Site::where('name', $fields->sitename)->first();
        if (!$choice) {
            return Action::danger("请先创建小编精选!");
        }
        foreach ($models as $movie) {
            Stick::create([
                'stickable_type'   => 'movies',
                'stickable_id'     => $movie->id,
                'place'            => $fields->place,
                'editor_choice_id' => $choice->id,
                'editor_id'        => $user->id,
                'site_id'          => optional($site)->id,
                'app_name'         => $fields->app,
            ]);
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            // Select::make('精选', 'choice')->options(EditorChoice::all()->pluck(['name',''])),
            Text::make('精选名', 'title'),
            Text::make('展示地点', 'place'),
            Text::make('展示站点', 'sitename'),
            Text::make('展示app', 'app'),
        ];
    }
}
