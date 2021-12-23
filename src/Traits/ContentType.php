<?php

namespace Haxibiao\Content\Traits;

trait ContentType
{
    /**
     * @Desc     资源类型
     * @DateTime 2018-07-24
     * @return   [type]     [description]
     */
    public function resoureTypeCN()
    {
        $type = get_class($this);
        if (str_contains($type, 'Post')) {
            return "动态";
        }
        if (str_contains($type, 'Issue')) {
            return "提问";
        }
        if (str_contains($type, 'Question')) {
            return "题目";
        }
        if (str_contains($type, 'Movie')) {
            return "电影";
        }
        return '文章';
    }
}
