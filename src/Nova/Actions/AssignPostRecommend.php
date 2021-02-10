<?php

namespace Haxibiao\Content\Nova\Actions;

use App\PostRecommended;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Nova;

class AssignPostRecommend extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = '加入推荐队列';

    public function uriKey()
    {
        return str_slug(Nova::humanize($this));
    }
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        \DB::beginTransaction();
        try {
            foreach ($models as $model) {
                $recommend          = new PostRecommended();
                $recommend->post_id = data_get($model, 'id');
                $recommend->save();
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            \DB::rollBack();
            return Action::danger('添加失败');
        }
        DB::commit();

        return Action::message('添加成功');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
//            Text::make('加入推荐队列顶部', 'top'),
            //            Text::make('加入推荐队列尾部', 'tail'),
        ];
    }
}
