# Aphrodite TODO

## 项目状态概览

- **项目类型**: AI-native PHP 框架 (意图驱动开发引擎)
- **PHP版本要求**: ^8.1
- **测试状态**: 51 tests, 76 assertions ✅ 全部通过
- **PHPStan**: No errors ✅
- **当前完成度**: 生产就绪

---

## 已完成功能

### 核心层

| 模块 | 文件 | 功能 |
|------|------|------|
| 数据库 | `Database/Connection.php`, `Database/QueryBuilder.php` | PDO连接、查询构建 |
| ORM | `ORM/Entity.php` | ActiveRecord 模式 |
| HTTP | `Http/Request.php`, `Http/Response.php` | 请求/响应封装 |
| 路由 | `Router/AdaptiveRouter.php` | RESTful 路由 |
| 验证 | `Validation/Validator.php` | 数据验证 |
| 中间件 | `Http/Middleware/*.php` | CORS、限流、日志 |
| 缓存 | `Cache/Cache.php` | 文件/内存缓存 |
| 日志 | `Logger/Logger.php` | 多通道日志 |
| 队列 | `Queue/Queue.php` | 异步任务 |
| CLI | `Console/Artisan.php` | 命令行工具 |
| 配置 | `Config/Config.php` | 配置管理 |
| 引擎 | `Engine/*.php` | AI 意图处理 |

### 新增功能

| 模块 | 文件 | 功能 |
|------|------|------|
| 会话 | `Session/Session.php` | Session管理、CSRF保护 |
| 事件 | `Events/EventDispatcher.php` | 事件系统 |
| 文件 | `FileSystem/FileUpload.php` | 文件上传处理 |

---

## 项目结构

```
Aphrodite/
├── docs/                    # 文档
│   ├── usage.md            # 使用指南
│   └── api.md             # API 参考
├── examples/               # 示例
│   └── blog.md           # Blog 示例
├── src/
│   ├── Cache/            # 缓存
│   ├── Config/           # 配置
│   ├── Console/          # CLI
│   ├── Database/         # 数据库
│   ├── DSL/             # DSL解析
│   ├── Engine/          # 核心引擎
│   ├── Events/          # 事件系统
│   ├── FileSystem/      # 文件系统
│   ├── Http/            # HTTP层
│   ├── Logger/          # 日志
│   ├── Monitor/          # 监控
│   ├── ORM/              # ORM
│   ├── Queue/           # 队列
│   ├── Router/          # 路由
│   ├── Session/         # 会话
│   └── Validation/      # 验证
├── tests/                 # 测试 (51 tests)
├── .env.example          # 环境变量
├── artisan               # CLI入口
├── composer.json
├── phpunit.xml
└── phpstan.neon         # PHPStan
```

---

## 使用命令

```bash
# 安装
composer create-project aphrodite/framework my-app

# 测试
vendor/bin/phpunit

# 静态分析
vendor/bin/phpstan analyse

# CLI
php artisan help
php artisan make:controller UserController
php artisan make:model User
php artisan make:migration create_users_table
```

---

## 快速开始

```php
// 1. 配置数据库
use Aphrodite\Database\Connection;
$pdo = Connection::getInstance(['database' => 'myapp']);

// 2. 定义模型
class User extends Entity {
    protected static function getTable(): string {
        return 'users';
    }
}

// 3. CRUD操作
$user = User::create(['name' => 'John', 'email' => 'john@example.com']);
$users = User::all();

// 4. 定义路由
$router = new AdaptiveRouter();
$router->get('/users', [UserController::class, 'index']);

// 5. 验证
$validator = Validator::make($data, [
    'email' => 'required|email',
    'password' => 'required|min_length:8'
]);

// 6. 事件监听
Events::listen('user.created', function ($user) {
    Log::info('User created: ' . $user->id);
});
```

---

*最后更新: 2026-03-02*
