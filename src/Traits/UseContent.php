<?php

namespace Haxibiao\Content\Traits;

/**
 * 用户使用content系统
 */
trait UseContent
{
    use Categorizable;
    use Taggable;
    use Collectable;

    use UseArticle;
    use UsePost;
}
