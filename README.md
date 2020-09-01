## haxibiao/content

> haxibiao/content 是哈希表内容管理系统,主要包含了以下的功能:

- Post - 动态
- Article - 文章
- Category - 分类
- Issue/Solution - 问答

## 安装步骤

1.  `composer.json`改动如下：
    在`repositories`中添加 vcs 类型远程仓库指向
    `http://code.haxibiao.cn/packages/haxibiao-content`
2.  执行`composer require haxibiao/content`
3.  执行`php artisan content:install` 发布包中的资源文件
4.  执行`php artisan migrate` 执行包中的迁移文件
5.  完成

### 更新日志

**1.1**

_Released on 2020-09-01_

- 加入付费问答与抖音视频本地上传
- 增加静态模型绑定,解决子类无法触发父类事件以及Model的扩展性问题
- 修复Video中的Width/Height等属性为null的情况 
- 修复部分GQL语法错误,以及函数命名不规范的问题
- package中模型加入$guarded属性，兼容填充数据时字段不一致问题
- Post中加上了PostOldPatch Trait解决工厂Article Post的兼容问题,并修复了事件通知
- 为方便工厂系项目集成,加入数据修复脚本 `CategoryReFactoringCommand` 与 `PostReFactoringCommand`完成数据修复
- 剔除冗余的失效路由代码,完成API与GQL的测试用例补充

### 如何完成更新？

> 远程仓库的 composer package 发生更新时如何进行更新操作呢？

1.  执行`composer update haxibiao/content`

## 使用方法

假设有`Article`模型：

```php
<?php

 namespace App;

 use Illuminate\Database\Eloquent\Model;
 use \Haxibiao\Content\Traits\Categorizable;

 class Article extends Model
 {
 	use Categorizable;

 }
```

给 Article 关联新的分类：

```php
<?php

namespace App;
$article = Article::find(1);

$article->categorize([1, 2, 3, 4, 5]);

return $article;
```

此时，`Article`模型已经关联了 Category 为`1, 2, 3, 4`和`5`
如果需要移除`Category`和`Article`之间的关系：

```php
<?php

namespace App;
$article = Article::find(1);

$article->uncategorize([3, 5]);

return $article;
```

此时 Article 模型只关联了 category id 为`1,2`和`4`的记录

如果需要重新 sync`Category`和`Article`的关系：

```php
<?php

namespace App;
$article = Article::find(1);

$article->recategorize([1, 5]);

return $article;
```

Article 模型当前只关联了 category id 为`1`和`5`的记录.

## GQL 接口说明

## Api 接口说明

1.  [查看专题详情](#查看专题详情)
2.  [查看专题下视频](#查看专题下视频)
3.  [专题图标上传](#专题图标上传)
4.  [专题更换图标](#专题更换图标)
5.  [专题更新信息](#专题更新信息)

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

| params    | must | desc     |
| --------- | ---- | -------- |
| api_token | yes  |          |
| logo      | yes  | 图片文件 |

## 专题更换图标

#### 请求方法

POST

#### 接口地址

api/category/{id}/edit-logo

| params    | must | desc     |
| --------- | ---- | -------- |
| api_token | yes  |          |
| logo      | yes  | 图片文件 |

## 专题更新信息

#### 请求方法

POST

#### 接口地址

api/category/{id}

| params    | must | desc |
| --------- | ---- | ---- |
| api_token | yes  |      |
| name      | no   |      |
| ...       | no   |      |

待补充...
