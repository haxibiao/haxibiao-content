## haxibiao/content

> haxibiao/content 是内容管理系统,主要包含了:

-   Category - Categorizable - 分类
-   Tag - Taggable - 标签
-   Collection - Collectable - 合集 (短视频动态合集，小说连载，漫画连载)
-   Stick - Stickable - 置顶内容（APP 首页，发现，网站频道首页）
-   Article - 文章 (长图文，小说，漫画)
-   Post - 动态 (短视频，短图文)
-   Issue - 问题（付费）
-   Solution - 解决方案

## 主要依赖

1. haxibiao/media

## 互相依赖

1. haxibiao/cms
2. haxibiao/sns

## 安装步骤

1.  `composer.json`改动如下：
    在`repositories`中添加 vcs 类型远程仓库指向
    `http://code.haxibiao.cn/packages/haxibiao-content`
2.  执行`composer require haxibiao/content`
3.  执行`php artisan content:install` 发布包中的资源文件
4.  执行`php artisan migrate` 执行包中的迁移文件
5.  完成

### 更新日志

_Released on 2020-11-24_

-   添加位置功能-对象 Location
-   需要执行`composer require saikiran/geohash`
-   在手动发布视频时，前端需要传入 Location 包含位置信息和经纬度信息

**1.3**
_Released on 2020-09-26_

-   能创建合集，可以将动态归类到合集中
-   能搜索合集，合集内能搜索动态 ([#f3d21](http://code.haxibiao.cn/packages/haxibiao-content/commit/f3d219fdddd8c725d3acab83203eea72e5d3ad0b))
-   合集添加集数 ([#7358c8](http://code.haxibiao.cn/packages/haxibiao-content/commit/7358c8ab5bf1d34a87d36f4379a01116f3862d95), [#d86e3](http://code.haxibiao.cn/packages/haxibiao-content/commit/d86e3ce8a1c0cd4481635abdc8c29876839299a0))
-   合集&视频&标签能查看真实的播放量 ([#58642](http://code.haxibiao.cn/packages/haxibiao-content/commit/58642d4d4d5c3745ef55db1ebaee32dc4b8e4192), [#36d27](http://code.haxibiao.cn/packages/haxibiao-content/commit/36d27b6172dbfad8f92ac1854e88f16b879d7dd5))
-   能利用 H5 分享合集 ([#51186](http://code.haxibiao.cn/packages/haxibiao-content/commit/511867b748780f570a8663b9a67c574bc5ce6a85), [#286b5](http://code.haxibiao.cn/packages/haxibiao-content/commit/286b54d824a7d28fa4f48352d56d3dd5992053f9))
-   采集抖音视频能分发到系统马甲号 ([#f0a15b0](http://code.haxibiao.cn/packages/haxibiao-content/commit/f0a15b0d92f1eb52a81d5ee82df7a735f0a78412), [#c6ae3](http://code.haxibiao.cn/packages/haxibiao-content/commit/c6ae3dc9e959ad0921be6128b724f418375957cf))
-   能根据抖音合集信息生成合集 ([#3424a](http://code.haxibiao.cn/packages/haxibiao-content/commit/3424a831001270b3a9cb962cf7e9189c0a35db28), [#7988a](http://code.haxibiao.cn/packages/haxibiao-content/commit/7988a6e5af6108ec0e7737cf0c85da03c8bf54e2))
-   移除冗余的任务奖励逻辑 ([#3e78f](http://code.haxibiao.cn/packages/haxibiao-content/commit/3e78f57f8e2c088c22840ac049259f98f8d1c111), [#c9a0bf](http://code.haxibiao.cn/packages/haxibiao-content/commit/c9a0bfee31b66b60669015fdf13f21e929a51379))

**1.2**

_Released on 2020-09-11_

-   发布采集加入精力点扣除逻辑([#b3d13eb1](http://code.haxibiao.cn/packages/haxibiao-content/commit/b3d13eb166019b4cd81e51f7a4698dc484c29fdb), [#1eb5f125](http://code.haxibiao.cn/packages/haxibiao-content/commit/1eb5f125712977bd1460bcf4ba704b28cd766426), [#326fda41](http://code.haxibiao.cn/packages/haxibiao-content/commit/326fda415b5b0774a5faf0fa31d28d15a8815055))
-   分享模版可进行配置 ([#560dfd4e](http://code.haxibiao.cn/packages/haxibiao-content/commit/560dfd4e92bb7b5e41bcee993ddf9a10e876ff89), [#e316924d](http://code.haxibiao.cn/packages/haxibiao-content/commit/e316924d647500a926a5895a569339b683762b0a) [#38ac327e](http://code.haxibiao.cn/packages/haxibiao-content/commit/38ac327eeb0150ec7ebafd6a9772a683cc136d2e))
-   修复 class 重复命名 ([#ab8c554e](http://code.haxibiao.cn/packages/haxibiao-content/commit/ab8c554e10788587a1542a935d1a5ee5a2a3ab86))
-   Article 模型修复 Image 关系 ([#b1f1402c](http://code.haxibiao.cn/packages/haxibiao-content/commit/b1f1402cfda9d7f2529fd77bdcb5448163b2c320), [#824ad122](http://code.haxibiao.cn/packages/haxibiao-content/commit/824ad122ee3fa7ffa24e314f771918b9ef0bd72d), [#ef5bca4a](http://code.haxibiao.cn/packages/haxibiao-content/commit/ef5bca4a9f0866bb82cfae19c770060c20a3d0cc))
-   增加分享链接计数 ([#1cffe56d](http://code.haxibiao.cn/packages/haxibiao-content/commit/1cffe56d51733aec58b4ef4d2003fbdb5d698213))

**1.1**

_Released on 2020-09-01_

-   加入付费问答与抖音视频本地上传
-   增加静态模型绑定,解决子类无法触发父类事件以及 Model 的扩展性问题
-   修复 Video 中的 Width/Height 等属性为 null 的情况
-   修复部分 GQL 语法错误,以及函数命名不规范的问题
-   package 中模型加入\$guarded 属性，兼容填充数据时字段不一致问题
-   Post 中加上了 PostOldPatch Trait 解决工厂 Article Post 的兼容问题,并修复了事件通知
-   为方便工厂系项目集成,加入数据修复脚本 `CategoryReFactoringCommand` 与 `PostReFactoringCommand`完成数据修复
-   剔除冗余的失效路由代码,完成 API 与 GQL 的测试用例补充

### 如何完成更新？

> 远程仓库的 composer package 发生更新时如何进行更新操作呢？

1.  执行`composer update haxibiao/content`

## 使用方法

假设有`Article`模型：

```php
<?php

 namespace App;

 use Illuminate\Database\Eloquent\Model;
 use \Haxibiao\Content\Traits\WithCategory;

 class Article extends Model
 {
 	use WithCategory;

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
