<?php

namespace Haxibiao\Content;

use Haxibiao\Breeze\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;

class EditorChoice extends Model
{
    use \Haxibiao\Content\Traits\Stickable;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array'
    ];

    public function getMorphClass()
    {
        return 'editor_choices';
    }

    public function movies(){
        $movieIds =  data_get($this,'data.movies',[]);
        $modelString = Relation::getMorphedModel('movies');
        return $modelString::whereIn('id',$movieIds);
    }

    public function resolveEditorChoices($root,$args, $context){
        $name = data_get($args,'name');
        $channel = data_get($args,'channel');

        return static::whereHas('related',function ($query)use($name,$channel){
            $query->where('name', $name)->whereChannel($channel);
        });
    }

    public function resovleEditorChoiceMovies($root,$args, $context){
        return $root->movies();
    }
}
