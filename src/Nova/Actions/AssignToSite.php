<?php

namespace Haxibiao\Content\Nova\Actions;

use Haxibiao\Content\Site;
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

class AssignToSite extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels, Actionable;

    public $name = '更新到站点';
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
        if (!isset($fields->site_id)) {
            return Action::danger('必须选中要更新的站点');
        }

        $site = Site::findOrFail($fields->site_id);

        $pushed_count = 0;
        DB::beginTransaction();
        $err = '';
        try {
            $urls = [];
            foreach ($models as $model) {
                $model->assignToSite($site->id);
                $urls[] = cms_url($model, $site);
            }
            //提交百度收录
            if ($fields->is_push && $site->ziyuan_token) {
                if (count($urls) >= 3000) {
                    //简单防止超过百度提交配额的过渡请求
                    $urls = array_slice($urls, 0, 3000);
                }

                $proxy       = env('CMS_BAIDU_PUSH_PROXY', null);
                $push_result = push_baidu($urls, $site->ziyuan_token, $site->domain, $proxy);
                if ($push_result == "成功") {
                    //提交收录成功，记录时间
                    foreach ($models as $model) {
                        update_baidu_pushed_at($model, $site);
                        ++$pushed_count;
                    }
                } else {
                    $err = $push_result;
                }

                //顺便提交下神马MIP数据
                if ($site->shenma_token && $site->shenma_owner_email) {
                    //神马没提示当日用量多少，不计较成败了
                    push_shenma($urls, $site->shenma_token, $site->domain, $site->shenma_owner_email);
                }
            }
        } catch (\Exception $e) {
            $err = $e->getMessage();
            Log::error($err);
            DB::rollBack();
            $msg = '数据批量变更失败，数据回滚';
            if (!is_prod_env()) {
                $msg .= $err;
            }
            return Action::danger($msg);
        }
        DB::commit();

        if ($fields->is_push && $pushed_count == 0) {
            return Action::danger('更新到站点成功' . count($models) . '条, 百度提交失败, 原因:' . $err);
        }
        return Action::message('更新到站点成功' . count($models) . '条, 百度提交成功' . $pushed_count . '条');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        $siteOptions = [];
        foreach (Site::all() as $site) {
            $siteOptions[$site->id] = $site->name . "(" . $site->domain . ")";
        }
        return [
            Select::make('站点', 'site_id')->options($siteOptions),
            Select::make('收录', 'is_push')->options([
                0 => '仅更新',
                1 => '提交百度',
            ]),
        ];
    }
}
