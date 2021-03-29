<?php

namespace Haxibiao\Content\Nova\Actions;

use App\Nova\EditorChoice;
use App\Site;
use App\Stick;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use OptimistDigital\MultiselectField\Multiselect;

class AddToSticks extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = '将该内容添加到精选库';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $user = \Auth::user();
        $site = Site::where('name', $fields->sitename)->first();

        foreach ($models as $movie) {
            $type  = $movie->getMorphClass();
            $stick = Stick::firstOrCreate([
                'stickable_type' => $type,
                'stickable_id'   => $movie->id,
                'place'          => $fields->place,
                'editor_id'      => $user->id,
                'site_id'        => optional($site)->id,
                'app_name'       => $fields->app,
            ]);
            if (isset($fields->cover)) {
                $stick->cover = Stick::saveDownloadImage($fields->cover);
                $stick->save();
            }
            if (isset($fields->editorChoice)) {
                $stick->editor_choice_id = json_decode($fields->editorChoice)[0];
                $stick->save();
            }
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
            Multiselect::make('精选栏', 'editorChoice')
                ->asyncResource(EditorChoice::class),
            Select::make('展示位置', 'place')->options(
                [
                    "index"      => "主页",
                    "search"     => "搜索页",
                    "movie"      => "电影页",
                    "collection" => "合集页",
                ]
            )->displayUsingLabels(),
            Text::make('展示站点（可为空）', 'sitename'),
            Text::make('展示app（可为空）', 'app')->default(config("app.name")),
            Image::make('封面图片', 'cover')
                ->store(function (Request $request) {
                    $file = $request->file('cover');
                    return Stick::saveDownloadImage($file);
                })->disableDownload(),

        ];
    }
}
