<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Media\Traits\WithMedia;

/**
 * 可用content系统管理的内容
 */
trait Contentable
{
    use Categorizable;
    use Taggable;
    use Collectable;

    use WithMedia;

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
        return '文章';
    }
}
