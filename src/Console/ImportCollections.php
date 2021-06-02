<?php

namespace Haxibiao\Content\Console;

use App\Collection;
use App\Image;
use App\Post;
use App\Spider;
use App\Tag;
use App\User;
use App\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportCollections extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:collections {origin?} {postCount?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导入合集视频 from $origin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $origin    = $this->argument('origin');
        $postCount = $this->argument('postCount');

        $this->info("start import collections");

        $this->importCollect($origin, $postCount);

        $this->info("finish import collections");

        return 1;
    }

    public function importCollect($origin = 'yxsp', $postCount = 3)
    {
        $origin    = is_null($origin) ? 'yxsp' : $origin;
        $postCount = is_null($postCount) ? 3 : $postCount;
        Collection::on($origin)->has('posts')->where('count_posts', '>=', $postCount)->chunk(20, function ($collections) {
            $this->info("import collections processing");
            foreach ($collections as $fromcollection) {
                $this->info("import collections " . $fromcollection->name);
                //开启数据库事务
                DB::beginTransaction();
                try {
                    //新建user
                    $fromUser        = $fromcollection->user;
                    $user_attributes = array_filter(array_except($fromUser->getAttributes(),
                        ['id', 'avatar', 'birthday', 'age', 'background']));
                    $intoUser = User::firstOrNew(
                        ['account' => $fromUser->account],
                        $user_attributes);
                    //更新头像
                    $intoUser->avatar = self::saveCompleteImage($fromUser->avatar);
                    $intoUser->saveDataOnly();

                    //确认该集合是否已经存在
                    $intoCollection = Collection::where('user_id', $intoUser->id)
                        ->where('name', $fromcollection->name)->first();
                    if (!$intoCollection) {
                        //创建collection
                        $intoCollection = self::copyModel($fromcollection, Collection::class,
                            null, $intoUser->id);
                    }
                    //更新合集封面
                    $intoCollection->logo       = self::saveCompleteImage($fromcollection->getRawOriginal('logo'));
                    $intoCollection->created_at = now();
                    $intoCollection->updated_at = now();
                    $intoCollection->saveDataOnly();

                    $fromPosts = $fromcollection->posts;
                    //移除已经存在的视频数据
                    $fromPosts->filter(function ($post) {
                        $video = $post->video;
                        return $video || !(bool) (Video::where('hash', $video->hash))->first();
                    });

                    $post_ids = [];
                    //遍历 Collection内的post并导入post相关数据
                    foreach ($fromcollection->posts as $fromPost) {

                        //导入post数据
                        $intoPost   = self::copyModel($fromPost, Post::class, 'description', $intoUser->id);
                        $post_ids[] = $intoPost->id;

                        //导入video数据
                        $fromVideo = $fromPost->video;
                        $intoVideo = self::copyModel($fromVideo, Video::class, 'hash', $intoUser->id);
                        //更新Video封面
                        $intoVideo->cover = self::saveCompleteImage($fromVideo->cover);
                        $intoCollection->saveDataOnly();

                        //导入spider数据
                        $fromSpider = $fromPost->spider;
                        if ($fromSpider) {
                            $intoSpider = self::copyModel($fromSpider, Spider::class, 'source_url', $intoUser->id);
                            //更新spider的spider_id
                            $intoSpider->spider_id = $intoVideo->id;
                            $intoSpider->saveDataOnly();
                        }

                        //导入image数据
                        $image_ids = [];
                        foreach ($fromPost->images as $fromImage) {
                            $intoImage       = self::copyModel($fromImage, Image::class, 'hash', $intoUser->id);
                            $fromImage->path = self::saveCompleteImage($fromImage->path);
                            $fromImage->saveDataOnly();
                            $image_ids[] = $intoImage->id;
                        }
                        //更新imageable
                        $intoPost->images()->syncWithoutDetaching($image_ids);

                        //导入tag数据
                        $tag_ids = [];
                        foreach ($fromPost->tags as $fromTag) {
                            if (!isset($fromTag->tag_name)) {
                                continue;
                            }
                            $intoTag   = self::copyModel($fromTag, Tag::class, 'name', $intoUser->id);
                            $tag_ids[] = $intoTag->id;
                        }
                        //更新taggable
                        $intoPost->tags()->syncWithoutDetaching($tag_ids);

                        //更新post的user_id,video_id,spider_id
                        $intoPost->video_id   = $intoVideo->id;
                        $intoPost->user_id    = $intoUser->id;
                        $intoPost->spider_id  = $intoSpider->id;
                        $intoPost->created_at = now();
                        $intoPost->updated_at = now();;
                        $intoPost->save();
                    }
                    //同步post到collection中
                    $intoCollection->posts()->syncWithoutDetaching($post_ids);
                    $intoCollection->updateCountPosts();
                    //事务提交
                    DB::commit();
                } catch (\Exception $ex) {
                    info($ex);
                    //数据库回滚
                    DB::rollBack();
                    return false;
                }
            }

        });

    }

    /**
     * 拷贝模型
     * @param $toModel eg: "App\Post"
     * @param $index:  column_name  eg: "hash"
     */
    public static function copyModel($fromObject, $toModel, $index = null, $user_id = null)
    {
        $object_attributes           = array_except($fromObject->getAttributes(), ['id', 'created_at', 'updated_at']);
        $object_attributes['status'] = Collection::STATUS_UNSIGN;
        $a                           = array_filter($object_attributes);
        info($a);
        foreach ($object_attributes as $key => $value) {
            if (self::isJsonCastable($key, $fromObject)) {
                $object_attributes[$key] = json_decode($value);

            }
            if (is_null($value)) {
                unset($object_attributes[$key]);
            }
        }

        if ($index) {
            $newObject = $toModel::firstOrNew(
                [$index => $fromObject->$index],
                $object_attributes);
        } else {
            $newObject = $toModel::make($object_attributes);
        }
        if ($user_id) {
            $newObject->user_id = $user_id;
        }
        $newObject->saveDataOnly();
        return $newObject;
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    public static function isJsonCastable($key, $model)
    {
        return $model->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * 相对路径转存为绝对路径https
     */
    public static function saveCompleteImage($oldImagePath)
    {
        //返回默认图片
        if (!isset($oldImagePath)) {
            return config('content.collection_default_logo');
        }
        //返回原链接
        if (str_contains($oldImagePath, 'http')) {
            return $oldImagePath;
        }
        //返回https链接
        $oldImagePath = "/" . $oldImagePath;
        $oldImagePath = str_replace('//', '/', $oldImagePath);
        return "https://" . config('content.origin_cos_domain') . $oldImagePath;

    }

    /**
     *将绝对路径图片重新上传当前app的的cos
     */
    public static function transferImage($oldImagePath)
    {
        $defaultImage = config('haxibiao-content.collection_default_logo');
        try {

            if (!$oldImagePath || $oldImagePath == $defaultImage) {
                //使用默认图片代替
                return $defaultImage;
            }

            $randId    = uniqid();
            $extension = pathinfo($oldImagePath, PATHINFO_EXTENSION);
            $cosPath   = 'images/' . $randId . '.' . $extension; //网络路径图片
            if (str_contains($oldImagePath, 'http')) {
                Storage::cloud()->put($cosPath, file_get_contents($oldImagePath));
                $newImagePath = cdnurl($cosPath);
                return $cosPath;

            }
            //处理绝对路径图片
            $COS_DOMAIN   = config('haxibiao-content.origin_cos_domain');
            $imagePrefix  = ends_with($COS_DOMAIN, "/") ?: $COS_DOMAIN . "/";
            $completePath = $imagePrefix . $oldImagePath;
            //上传到cos
            Storage::cloud()->put($cosPath, file_get_contents($completePath));
            return $cosPath;

        } catch (\Exception $ex) {
            info("transferImage 图片转换异常");
            info($ex->getMessage());
        }
        return $defaultImage;
    }
}
