<?php

namespace Haxibiao\Content\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Nova;

class RemoveRelationMovie extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;
    public $name = '移除电影关系';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if ($fields->status == 1) {
            foreach ($models as $model) {
                $model->update(['movie_id' => null]);
            }
            return Action::message('移除关联成功');
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
            Select::make("移除确认", 'status')->options([
                1  => "确定移除",
                -1 => "我手滑了",
            ]),
        ];
    }

    public function uriKey()
    {
        return str_slug(Nova::humanize($this));
    }
}
