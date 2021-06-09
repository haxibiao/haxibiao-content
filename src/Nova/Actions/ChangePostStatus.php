<?php

namespace Haxibiao\Content\Nova\Actions;

use App\Post;
use App\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Nova;

class ChangePostStatus extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels, Actionable;

    public $name = '状态变更';
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
        if (!isset($fields->type) and !isset($fields->status) and !isset($fields->category)) {
            return Action::danger('状态或者类型或者分类不能为空！');
        }

        DB::beginTransaction();
        try {
            foreach ($models as $model) {
                if (isset($fields->status)) {
                    $model->status = $fields->status;
                    //如果下架，相应的视频应该也要下架
                    if ($fields->status == -1) {
                        $video         = $model->video;
                        $video->status = Video::FAILED_STATUS;
                        $video->save();

                    }
                }
                $model->save();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return Action::danger('数据批量变更失败，数据回滚');
        }
        DB::commit();

        return Action::message('修改成功!,成功修改掉' . count($models) . '条数据');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Select::make('状态', 'status')->options(
                Post::getStatuses()
            ),
        ];
    }
}
