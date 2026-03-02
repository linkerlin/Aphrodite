# Aphrodite Blog 示例

这是一个基于 Aphrodite 框架的简单博客应用示例。

## 功能

- 文章 CRUD 操作
- 用户认证
- 评论系统
- RESTful API

## 项目结构

```
blog/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── PostController.php
│   │       └── UserController.php
│   └── Models/
│       ├── Post.php
│       ├── User.php
│       └── Comment.php
├── database/
│   └── migrations/
├── public/
│   └── index.php
├── routes/
│   └── web.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── queue/
├── .env
├── artisan
└── composer.json
```

## 数据库设计

### users 表
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### posts 表
```sql
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### comments 表
```sql
CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 核心代码

### User 模型

```php
<?php

namespace App\Models;

use Aphrodite\ORM\Entity;

class User extends Entity
{
    protected static function getTable(): string
    {
        return 'users';
    }
    
    public function posts()
    {
        return static::where('user_id', $this->id);
    }
}
```

### Post 模型

```php
<?php

namespace App\Models;

use Aphrodite\ORM\Entity;

class Post extends Entity
{
    protected static function getTable(): string
    {
        return 'posts';
    }
    
    public function comments()
    {
        return static::where('post_id', $this->id);
    }
    
    public function author()
    {
        return User::find($this->user_id);
    }
}
```

### PostController

```php
<?php

namespace App\Http\Controllers;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Validation\Validator;
use App\Models\Post;
use App\Models\User;

class PostController
{
    public function index(Request $request): Response
    {
        $posts = Post::all();
        return Response::success($posts);
    }
    
    public function show(Request $request, array $params): Response
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            return Response::notFound('Post not found');
        }
        
        return Response::success($post);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min_length:3|max_length:255',
            'content' => 'required|min_length:10',
            'user_id' => 'required|numeric'
        ]);
        
        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }
        
        $post = Post::create($validator->validated());
        
        return Response::success($post, 'Post created successfully', 201);
    }
    
    public function update(Request $request, array $params): Response
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            return Response::notFound('Post not found');
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'min_length:3|max_length:255',
            'content' => 'min_length:10'
        ]);
        
        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }
        
        $post->fill($validator->validated());
        $post->save();
        
        return Response::success($post, 'Post updated successfully');
    }
    
    public function destroy(Request $request, array $params): Response
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            return Response::notFound('Post not found');
        }
        
        $post->delete();
        
        return Response::success(['deleted' => true], 'Post deleted successfully');
    }
}
```

### 路由配置

```php
<?php

use Aphrodite\Router\AdaptiveRouter;
use Aphrodite\Http\Middleware\CorsMiddleware;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;

$router = new AdaptiveRouter();

// 应用中间件
$router->group(['prefix' => '/api', 'middleware' => [CorsMiddleware::class]], function ($router) {
    // 用户
    $router->post('/register', [UserController::class, 'register']);
    $router->post('/login', [UserController::class, 'login']);
    
    // 文章
    $router->get('/posts', [PostController::class, 'index']);
    $router->get('/posts/{id}', [PostController::class, 'show']);
    $router->post('/posts', [PostController::class, 'store']);
    $router->put('/posts/{id}', [PostController::class, 'update']);
    $router->delete('/posts/{id}', [PostController::class, 'destroy']);
});

return $router;
```

### 入口文件

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Aphrodite\Http\Request;
use Aphrodite\Logger\Log;

// 加载环境配置
Aphrodite\Config\Environment::load(__DIR__ . '/../.env');

// 初始化日志
Log::createFileLogger(__DIR__ . '/../storage/logs/app.log');

// 获取请求
$request = Request::capture();

// 加载路由
$routes = require __DIR__ . '/../routes/web.php';

// 处理请求
$response = $routes->handle($request);

// 发送响应
$response->send();
```

## API 示例

### 注册用户

```bash
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

响应:
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### 登录

```bash
POST /api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

响应:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
    }
}
```

### 创建文章

```bash
POST /api/posts
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "title": "My First Post",
    "content": "This is the content of my first blog post.",
    "user_id": 1
}
```

响应:
```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 1,
        "title": "My First Post",
        "content": "This is the content of my first blog post.",
        "user_id": 1
    }
}
```

### 获取文章列表

```bash
GET /api/posts
```

响应:
```json
{
    "success": true,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "title": "My First Post",
            "content": "This is the content of my first blog post.",
            "user_id": 1,
            "created_at": "2026-03-02 10:00:00"
        }
    ]
}
```

### 获取单篇文章

```bash
GET /api/posts/1
```

响应:
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "title": "My First Post",
        "content": "This is the content of my first blog post.",
        "user_id": 1,
        "created_at": "2026-03-02 10:00:00",
        "updated_at": "2026-03-02 10:00:00"
    }
}
```

### 更新文章

```bash
PUT /api/posts/1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "title": "Updated Title",
    "content": "Updated content"
}
```

### 删除文章

```bash
DELETE /api/posts/1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

响应:
```json
{
    "success": true,
    "message": "Post deleted successfully",
    "data": {
        "deleted": true
    }
}
```

## 运行

```bash
# 安装依赖
composer install

# 配置环境
cp .env.example .env

# 创建数据库
mysql -u root -p < database/schema.sql

# 启动内置服务器
php -S localhost:8080 -t public

# 或者使用 Artisan
php artisan serve
```
