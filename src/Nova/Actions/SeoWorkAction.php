<?php

namespace Haxibiao\Content\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Nova;

class SeoWorkAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'SEO操作';
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
        foreach ($models as $model) {
            $command = "seo:work";
            $data    = ["--domain" => $model->domain];
            if ($sync_count = $fields->sync_count) {
                $data["--sync_count"] = $sync_count;
            }
            if ($type = $fields->type) {
                $data["--type"] = $type;
            }
            if ($submit_count = $fields->submit_count) {
                if ($submit_count % 100 > 0) {
                    return Action::danger("推送数量必须是100的倍数");
                }
                $data["--submit_count"] = $submit_count;
            }

            Artisan::call("seo:work", $data);
        }
        return Action::message('操作成功，后台正在运行中！');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make('更新站点内容数量（同步自哈希云）', 'sync_count'),
            Select::make('推送URL类型', 'type')->options([
                'movie'   => '长视频',
                'video'   => '短视频',
                'article' => '文章',
            ]),
            Text::make('推送URL数量（100的倍数）', 'submit_count'),
        ];
    }
}
