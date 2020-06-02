<?php

use Haxibiao\Category\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

if ( ! function_exists('get_categories'))
{
    function get_categories($full = 0, $type = 'article', $for_parent = 0)
    {
        $categories = [];
        if ($for_parent) {
            $categories[0] = null;
        }
        $category_items = Category::where('type', $type)->orderBy('order', 'desc')->get();
        foreach ($category_items as $item) {
            if ($item->level == 0) {
                $categories[$item->id] = $full ? $item : $item->name;
                if ($item->has_child) {
                    foreach ($category_items as $item_sub) {
                        if ($item_sub->parent_id == $item->id) {
                            $categories[$item_sub->id] = $full ? $item_sub : ' -- ' . $item_sub->name;
                            foreach ($category_items as $item_subsub) {
                                if ($item_subsub->parent_id == $item_sub->id) {
                                    $categories[$item_subsub->id] = $full ? $item_subsub : ' ---- ' . $item_subsub->name;
                                }
                            }
                        }
                    }
                }
            }
        }
        $categories = \Illuminate\Support\Collection::make($categories);
        return $categories;
    }
}

if ( ! function_exists('get_top_categories_count'))
{
    function get_top_categories_count()
    {
        $categories = [];

        $stick_categories = get_stick_categories();
        $leftCount        = 7 - count($stick_categories);
        return $leftCount;
    }
}

if ( ! function_exists('get_stick_categories'))
{
    function get_stick_categories($all = false, $index = false)
    {
        $categories = [];
        if (Storage::exists("stick_categories")) {
            $json  = Storage::get('stick_categories');
            $items = json_decode($json, true);
            foreach ($items as $item) {
                if (!$all) {
                    //expire
                    if (Carbon::createFromTimestamp($item['timestamp'])->addDays($item['expire']) < Carbon::now()) {
                        continue;
                    }
                }

                $category = category::find($item['category_id']);

                if ($index && $category) {
                    $categories[] = $category;
                    continue;
                }
                if ($category) {
                    $category->reason     = !empty($item['reason']) ? $item['reason'] : null;
                    $category->expire     = $item['expire'];
                    $category->stick_time = diffForHumansCN(Carbon::createFromTimestamp($item['timestamp']));
                    $categories[]         = $category;
                }
            }
        }
        return $categories;
    }
}

if ( ! function_exists('get_top_categoires'))
{
    function get_top_categoires($top_categoires)
    {
        $categories = [];

        $stick_categories = get_stick_categories(false, true);
        foreach ($top_categoires as $category) {
            $categories[] = $category;
        }

        $categories = array_merge($stick_categories, $categories);
        $categories = new collection($categories);
        $categories = $categories->unique();

        return $categories;
    }
}

if ( ! function_exists('get_stick_video_categories'))
{
    function get_stick_video_categories($all = false, $index = false)
    {
        $video_categories = [];
        if (Storage::exists("stick_video_categories")) {
            $json  = Storage::get('stick_video_categories');
            $items = json_decode($json, true);
            foreach ($items as $item) {
                if (!$all) {
                    //expire
                    if (Carbon::createFromTimestamp($item['timestamp'])->addDays($item['expire']) < Carbon::now()) {
                        continue;
                    }
                }

                $category = Category::find($item['category_id']);

                if ($index && $category) {
                    $video_categories[] = $category;
                    continue;
                }
                if ($category) {
                    $category->reason     = !empty($item['reason']) ? $item['reason'] : null;
                    $category->expire     = $item['expire'];
                    $category->stick_time = diffForHumansCN(Carbon::createFromTimestamp($item['timestamp']));
                    $video_categories[]   = $category;
                }
            }
        }
        return $video_categories;
    }
}

if ( ! function_exists('stick_category'))
{
    function stick_category($data, $auto = false)
    {
        $items = [];

        if (Storage::exists("stick_categories")) {
            $json = Storage::get("stick_categories");

            foreach (json_decode($json, true) as $item) {
                //expire
                if (Carbon::createFromTimestamp($item['timestamp'])->addDays($item['expire']) < Carbon::now()) {
                    continue;
                }
                $items[] = $item;
            }
        }

        $data['timestamp'] = time();
        if ($auto) {
            $items[] = $data;
        } else {
            $items = array_merge([$data], $items);
        }
        $json = json_encode($items);
        Storage::put("stick_categories", $json);
    }
}

if ( ! function_exists('stick_video_category'))
{
    function stick_video_category($data, $auto = false)
    {
        $items = [];

        if (Storage::exists("stick_video_categories")) {
            $json = Storage::get("stick_video_categories");

            foreach (json_decode($json, true) as $item) {
                //expire
                if (Carbon::createFromTimestamp($item['timestamp'])->addDays($item['expire']) < Carbon::now()) {
                    continue;
                }
                $items[] = $item;
            }
        }

        $data['timestamp'] = time();
        if ($auto) {
            $items[] = $data;
        } else {
            $items = array_merge([$data], $items);
        }
        $json = json_encode($items);
        Storage::put("stick_video_categories", $json);
    }
}