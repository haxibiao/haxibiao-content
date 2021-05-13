<?php

namespace Haxibiao\Content\Traits;

use App\Exceptions\GQLException;
use App\Image;
use App\Solution;
use App\Visit;

trait SolutionResolvers
{
    public function addSolutionResolver($root, array $args, $context, $info)
    {
        $user               = getUser();
        $solution           = new Solution();
        $solution->user_id  = $user->id;
        $solution->issue_id = $args['issue_id'];
        $solution->answer   = $args['answer'];
        $solution->save();

        //添加评论图片 链接格式
        if (!empty($args['image_urls']) && is_array($args['image_urls'])) {
            //根据链接获取图片id: 2137.jpg
            $image_ids = array_map(function ($url) {
                return intval(pathinfo($url)['filename']);
            }, $args['image_urls']);
            $solution->images()->sync($image_ids);
            $solution->image_url = array_first($args['image_urls']);
            $solution->save();
        }
        //添加评论图片 base64格式
        if (!empty($args['images']) && is_array($args['images'])) {
            foreach ($args['images'] as $image) {
                $image = Image::saveImage($image);
                $solution->images()->attach($image->id);
            }
            $solution->image_url = $solution->images()->first()->path;
            $solution->save();
        }
        return $solution;
    }

    public function querySolutionsResolver($root, array $args, $context, $info)
    {
        $issue_id  = $args['issue_id'];
        $solutions = Solution::where('issue_id', $issue_id);
        if (currentUser()) {
            Visit::saveVisits(getUser(), $solutions->get(), 'solutions');
        }
        return $solutions;
    }

    public function querySolutionResolver($root, array $args, $context, $info)
    {
        $issue_id = $args['id'];
        $solution = Solution::find($issue_id);
        if (currentUser()) {
            if (getUserId() != $solution->user_id) {
                Visit::saveVisits(getUser(), [$solution], 'solutions');
            }
        }
        return $solution;
    }

    public function mySolutionsResolver($root, array $args, $context, $info)
    {
        $user      = getUser();
        $user_id   = $args['user_id'] ?: $user;
        $solutions = Solution::where('user_id', $user_id);
        return $solutions;
    }

    public function deleteSolutionResolver($rootValue, $args, $context, $resolveInfo)
    {

        $solution_id = $args['id'];
        $solution    = Solution::findOrFail($solution_id);
        $user_id     = $solution->user_id;
        throw_if($user_id != getUserId(), GQLException::class, '删除失败，该回答不是你发布的');
        $solution->delete();
        return $solution;
    }
}
