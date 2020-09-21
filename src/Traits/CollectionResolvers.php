<?php

namespace Haxibiao\Content\Traits;

use App\Collection;
use App\Image;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait CollectionResolvers
{
    // 创建合集信息
    public function resolveCreateCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $name = Arr::get($args, 'name');
        $logo = Arr::get($args, 'logo');
        $type = Arr::get($args, 'type');

        if ($logo){
            $image = Image::saveImage($logo);
            $logo = $image->path;
        }else{
            $logo=User::AVATAR_DEFAULT;
        }

        $collection = Collection::firstOrNew([
            'user_id'=>getUser()->id,
            'name' => $name,
            'logo' => $logo,
            'type' => $type,
            'status'=>1
        ]);
        $collection->save();
        return $collection;
    }

    // 创建合集信息
    public function resolveUpdateCollection($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $collection_id = Arr::get($args, 'collection_id');
        $collection = Collection::findOrFail($collection_id);
        $collection->update([
            'name' => Arr::get($args, 'name',$collection->name),
            'type' => Arr::get($args, 'type',$collection->type),
        ]);
        $logo = Arr::get($args, 'logo');
        if ($logo){
            $image = Image::saveImage($logo);
            $logo = $image->path;
        }else{
            $logo=$collection->logo;
        }
        $collection->update([
            'logo' => $logo,
        ]);
        return $collection;
    }

}
