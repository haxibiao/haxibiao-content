<?php

namespace Haxibiao\Content\Traits;

trait StickRepo
{
    /**
     * 上传合集封面
     */
    public static function saveDownloadImage($file)
    {
        if ($file) {
            $cover   = '/stick/' . uniqid() . '_' . time() . '.png';
            $cosDisk = \Storage::cloud();
            $cosDisk->put($cover, \file_get_contents($file->path()));
            return cdnurl($cover);
        }
    }
}
