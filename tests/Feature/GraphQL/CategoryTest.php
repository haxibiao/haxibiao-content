<?php

namespace haxibiao\content\Tests\Feature\GraphQL;

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class CategoryTest extends TestCase
{
    use CreatesApplication;

    // public function testCategoriesQuery()
    // {
    //        $query = file_get_contents(__DIR__ . '/Category/CategoriesQuery.gql');
    //
    //        //hot分类
    //        $variables = [
    //            'filter'=> "hot" ,
    //        ];
    //
    //        $this->startGraphQL($query, $variables);
    //
    //        //other分类
    //        $variables = [
    //            'filter'=> "other" ,
    //        ];
    //
    //        $this->startGraphQL($query, $variables);
    // }
}
