<?php

namespace Haxibiao\Content\Jobs;

use Haxibiao\Content\Post;
use Haxibiao\Helpers\utils\FFMpegUtils;
use Haxibiao\Helpers\utils\VodUtils;
use Haxibiao\Media\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class MakeMp4ByM3U8 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 300;

    protected $video, $series, $start, $second;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Video $video, $series, $start, $second)
    {
        $this->video  = $video;
        $this->series = $series;
        $this->start  = $start;
        $this->second = $second;
        $this->onQueue('makemp4');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = $this->video;
        try {
            // 使用vod拉取上传
            $fileID = FFMpegUtils::M3u8ConvertIntoMp4($this->series->play_url, $this->start, $this->second, "{$this->video->id}.mp4");
            VodUtils::makeCoverAndSnapshots($fileID);
            $videoInfo = null;
            $cover     = null;
            do {
                // 等待截图完成
                sleep(5);
                $videoInfo = VodUtils::getVideoInfo($fileID);
                $cover     = data_get($videoInfo, 'basicInfo.coverUrl');
            } while ($cover == null);
            $width     = data_get($videoInfo, 'metaData.width');
            $path      = data_get($videoInfo, 'basicInfo.sourceVideoUrl');
            $height    = data_get($videoInfo, 'metaData.height');
            $duration  = data_get($videoInfo, 'metaData.duration');
            $extension = data_get($videoInfo, 'basicInfo.type');
            $video->update([
                'width'               => $width,
                'height'              => $height,
                'duration'            => $duration,
                'path'                => $path,
                'cover'               => $cover,
                'disk'                => 'vod',
                'extension'           => $extension,
                'tencent_vod_file_id' => $fileID,
            ]);
            if ($post = $video->post) {
                $post->update([
                    'cover'  => $cover,
                    'status' => Post::STATUS_ENABLE,
                ]);
            }
            ProcessQCVideo::pushUrlCacheWithVODUrl($path);
        } catch (\Throwable $th) {
            info($th->getMessage());
            if (isset($video)) {
                Post::where('video_id', $video->id)->delete();
                $video->delete();
            }
        }
    }
}
