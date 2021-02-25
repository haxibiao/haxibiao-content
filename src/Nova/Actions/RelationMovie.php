<?php

namespace Haxibiao\Content\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Nova;
use OptimistDigital\MultiselectField\Multiselect;

class RelationMovie extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;
    public $name = '关联电影';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if (!isset($fields->movie_id) && !isset($fields->select_movie_id)) {
            return Action::danger('请选择要关联的长视频！');
        }

        $movie = \App\Movie::find($fields->movie_id ?? $fields->select_movie_id);
        if (empty($movie)) {
            return Action::danger('关联失败，没有该长视频！');
        }

        foreach ($models as $model) {
            if ($model->movie) {
                return Action::danger('关联失败，该动态已有关联长视频！');
            }
            $model->update(['movie_id' => $movie->id]);
        }
        return Action::message('关联成功');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        $data = \App\Movie::query()->take(30)->select('name', 'id')->pluck('name', 'id')->toArray();
        return [
            Number::make('电影ID', 'movie_id'),
            Multiselect::make('关联电影', 'select_movie_id')
                ->belongsTo(Movie::class)
                ->asyncResource(Movie::class)->onlyOnForms(),
        ];

    }

    public function uriKey()
    {
        return str_slug(Nova::humanize($this));
    }
}
