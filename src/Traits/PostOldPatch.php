<?php

namespace Haxibiao\Content\Traits;

use Haxibiao\Content\Post;

/**
 * 兼容以前Article
 * Trait PostOldAttrs
 * @package Haxibiao\Content\Traits
 */
trait PostOldPatch
{
    //
    public function patchResolveRecommendPosts($root, $args, $context, $info)
    {
        $currentPage = $args['page'];
        $perPage     = $args['count'];
        $total       = Post::publish()->count();
        $mixPosts = Post::getRecommendPosts($perPage);
        return  new \Illuminate\Pagination\LengthAwarePaginator($mixPosts, $total, $perPage, $currentPage);
    }


    public function getRemarkAttribute(){

    }

    public function getIssueAttribute(){

    }

    public function getQuestionRewardAttribute(){
        return 0;
    }

    public function getAnsweredStatusAttribute(){
        return 0;
    }

    public function getIsAdPositionAttribute(){
        if($this->is_ad){
            return $this->is_ad;
        }
        return false;
    }

    public function getCommentsAttribute(){

    }

    public function getTipsAttribute(){

    }

    public function getCollectionAttribute(){

    }

    public function getCategoryAttribute(){
        return $this->categories()->first();
    }

    public function getArtilceImagesAttribute(){

    }

    public function getCountTipsAttribute(){
        return 0;
    }

    public function getCountRepliesAttribute(){
        return 0;
    }

    public function getCountWordsAttribute(){
        return 0;
    }

    public function getHitsAttribute(){
        return 0;
    }

    public function getSubmitAttribute(){
        return $this->status;
    }

    public function getFavoritedIdAttribute(){

    }

    public function getFavoritedAttribute(){

    }

    public function getLikedIdAttribute(){

    }

    public function getVideoUrlAttribute(){

    }

    public function getBodyAttribute(){
        return $this->content;
    }

    public function getSubjectDescriptionAttribute(){
        return $this->content;
    }

    public function getSubjectAttribute(){
        return $this->content;
    }

    public function getTypeAttribute(){
        if($this->video_id){
            return 'video';
        }
        return 'post';
    }
}
