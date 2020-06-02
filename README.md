## haxibiao/category
 
 > haxibiao/category 是哈希表分类专题相关开发包存放的地方
 

 ## 安装步骤
 
 1. `composer.json`改动如下：
 在`repositories`中添加 vcs 类型远程仓库指向 
 `http://code.haxibiao.cn/packages/haxibiao-category` 
 1. 执行`composer require haxibiao/category`
 2. 如果不是laravel 5.6以上，需要执行`php artisan config:install`
 3. 完成
 
 ### 如何完成更新？
 > 远程仓库的composer package发生更新时如何进行更新操作呢？
 1. 执行`composer update haxibiao/category`
 
 
 ## 使用方法
 假设有`Article`模型：
 
```php
<?php
 
 namespace App;
 
 use Illuminate\Database\Eloquent\Model;
 use \Haxibiao\Category\Traits\Categorizable;
 
 class Article extends Model
 {
 	use Categorizable;
 
 }
 ```
 给Article关联新的分类：
 ```php
<?php
 
 namespace App;
 $article = Article::find(1);
 
 $article->categorize([1, 2, 3, 4, 5]);
 
 return $article;
````
此时，`Article`模型已经关联了Category为`1, 2, 3, 4`和`5` 
如果需要移除`Category`和`Article`之间的关系：
 ```php
<?php
 
 namespace App;
 $article = Article::find(1);
 
 $article->uncategorize([3, 5]);
 
 return $article;
````
此时Article模型只关联了category id为`1,2`和`4`的记录

如果需要重新sync`Category`和`Article`的关系：
 ```php
<?php

namespace App;
$article = Article::find(1);

$article->recategorize([1, 5]);

return $article;
````
Article模型当前只关联了category id为`1`和`5`的记录.
 ## GQL接口说明
 
 ## Api接口说明
 1. [查看专题详情](#查看专题详情)
 2. [查看专题下视频](#查看专题下视频)
 3. [专题图标上传](#专题图标上传)
 4. [专题更换图标](#专题更换图标)
 5. [专题更新信息](#专题更新信息)

 
## 查看专题详情
#### 请求方法 
GET 
#### 接口地址
api/category/{id}

## 查看专题下视频
#### 请求方法 
Any 
#### 接口地址
api/category/{category_id}/videos

## 专题图标上传
#### 请求方法 
POST 
#### 接口地址
api/category/new-logo

| params | must | desc |
| ---- | ---- | ---- |
| api_token | yes |  |
| logo | yes| 图片文件 |

## 专题更换图标
#### 请求方法 
POST 
#### 接口地址
api/category/{id}/edit-logo

| params | must | desc |
| ---- | ---- | ---- |
| api_token | yes |  |
| logo | yes| 图片文件 |

## 专题更新信息
#### 请求方法 
POST 
#### 接口地址
api/category/{id}

| params | must | desc |
| ---- | ---- | ---- |
| api_token | yes |  |
| name | no|  |
| ... | no|  |

待补充...