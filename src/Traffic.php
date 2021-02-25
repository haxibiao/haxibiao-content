<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 专注seo有效流量分析，不关心普通流量数据
 */
class Traffic extends Model
{
    use HasFactory;

    protected $guarded = [];
}
