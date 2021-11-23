<?php

namespace Haxibiao\Content\Http\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CmsController extends Controller
{
    public function getRedirectUrls()
    {
        return cache()->get(get_sub_domain() . '_redirect_urls');
    }

    public function putRedirectUrls()
    {
        $urls         = request('urls');
        $cache_domain = get_sub_domain();
        Cache::forever($cache_domain . '_redirect_urls', $urls);
        return 'success';
    }
}
