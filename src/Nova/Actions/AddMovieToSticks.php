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
                'editor_id'        => getUserId(),
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
            Text::make('精选名称', 'title')->suggestions([
                '影厅推荐',
                '精选合集',
            ]),
            Text::make('展示位置', 'place')->suggestions([
                '影厅顶部',
                '合集顶部',
            ]),
            Text::make('展示站点（可为空）', 'sitename'),
            Text::make('展示app（可为空）', 'app'),
        ];
    }
}
