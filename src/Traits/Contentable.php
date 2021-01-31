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
}
