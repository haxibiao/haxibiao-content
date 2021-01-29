<?php

namespace Haxibiao\Content\Jobs;

use Haxibiao\Helpers\utils\VodUtils;
use Haxibiao\Media\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Vod\V20180717\Models\PushUrlCacheRequest;
use TencentCloud\Vod\V20180717\VodClient;

class ProcessQCVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $video;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
        $this->onQueue('upload');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video  = $this->video;
        $fileID = $video->tencent_vod_file_id;
        VodUtils::makeCoverAndSnapshots($fileID);
        sleep(5);
        $videoInfo       = VodUtils::getVideoInfo($fileID);
        $video->duration = data_get($videoInfo, 'basicInfo.duration');
        $video->cover    = data_get($videoInfo, 'basicInfo.coverUrl');
        $video->height   = data_get($videoInfo, 'metaData.height');
        $video->width    = data_get($videoInfo, 'metaData.width');
        $video->save();
    }

    /**
     * VOD视频资源预热
     */
    public static function pushUrlCacheWithVODUrl($url)
    {
        //VOD预热
        $cred        = new Credential(config('vod.secret_id'), config('vod.secret_key'));
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);

        $client = new VodClient($cred, "ap-guangzhou", $clientProfile);
        $req    = new PushUrlCacheRequest();
        $params = '{"Urls":["' . $url . '"]}';

        $req->fromJsonString($params);
        $resp = $client->PushUrlCache($req);

        return $resp->toJsonString();
    }
}
