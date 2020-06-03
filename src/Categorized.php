<?php

namespace haxibiao\content;

use Illuminate\Database\Eloquent\Model;

class Categorized extends Model
{
    protected $table = 'categorizeds';

    protected $fillable = [
        'category_id',
        'categorized_id',
        'categorized_type',
    ];

    public function categorized()
    {
        return $this->morphTo();
    }
}
