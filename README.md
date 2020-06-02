php artisan vendor:publish  --provider="Haxibiao\Category\CategoryServiceProvider" --force

php artisan migrate

注册Provider

```json
{
  "type": "path",
  "url": "./packages/haxibiao-category",
  "options": {
    "symlink": true
  }
}
```
    
    
composer require haxibiao/category


php artisan vendor:publish --provider="Haxibiao\Categorizable\CategorizableServiceProvider"


php artisan migrate


```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Haxibiao\Categorizable\Traits\Categorizable;

class Article extends Model
{
	use Categorizable;

}
```


## haxibiao/category
 
 > haxibiao/category 是哈希表分类专题相关开发包存放的地方
 
 
 ## 安装步骤
 
 1. `composer.json`改动如下：
 在`repositories`中添加 vcs 类型远程仓库指向 
 `http://code.haxibiao.cn/packages/haxibiao-category` 
 1. 执行`composer require haxibiao/config`
 2. 如果不是laravel 5.6以上，需要执行`php artisan config:install`
 3. 完成
 
 ### 如何完成更新？
 > 远程仓库的composer package发生更新时如何进行更新操作呢？
 1. 执行`composer update haxibiao/config`
 
 
 ## GQL接口说明
 
 ## Api接口说明