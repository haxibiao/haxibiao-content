<?php

namespace Haxibiao\Content\Traits;
trait IssueAttrs
{

    public function getImageAttribute()
    {
        return $this->images->first();
    }
    public function getImage1Attribute()
    {
        return $this->images->first();
    }

    public function getImage2Attribute()
    {
        return $this->images->first();
    }

    public function getImage3Attribute()
    {
        return $this->images->first();
    }
}