# Aphrodite 框架使用指南

## 快速开始

### 安装

```bash
composer create-project aphrodite/framework my-app
cd my-app
```

### 目录结构

```
my-app/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── ...
├── src/
│   ├── Cache/
│   ├── Config/
│   ├── Console/
│   ├── Database/
│   ├── Engine/
│   ├── Http/
│   ├── Logger/
│   ├── ORM/
│   ├── Queue/
│   ├── Router/
│   └── Validation/
├── tests/
├── storage/
│   ├── cache/
│   ├── logs/
│   └── queue/
├── artisan
├── composer.json
└── .env
```

## 核心功能

### 1. 数据库 ORM

```php
use Aphrodite\ORM\Entity;

// 定义模型
class User extends Entity
{
    protected static function getTable(): string
    {
        return 'users';
    }
}

// 创建记录
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// 查询记录
$user = User::find(1);
$users = User::all();
$users = User::where('status', '=', 'active');

// 更新记录
$user->name = 'Jane Doe';
$user->save();

// 删除记录
$user->delete();
```

### 2. 查询构建器

```php
use Aphrodite\Database\QueryBuilder;
use Aphrodite\Database\Connection;

// 获取 PDO 实例
$pdo = Connection::getInstance();

// 使用查询构建器
$users = QueryBuilder::table($pdo, 'users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// 插入记录
$id = QueryBuilder::table($pdo, 'users')
    ->values([
        'name' => 'John',
        'email' => 'john@example.com'
    ])
    ->insert();
```

### 3. HTTP 请求/响应

```php
use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

// 获取请求
$request = Request::capture();

// 获取输入
$name = $request->input('name');
$email = $request->post('email');
$token = $request->header('Authorization');

// 响应
return Response::json(['user' => $data]);
return Response::success($data, '操作成功');
return Response::error('错误信息', 400);
return Response::redirect('/path');
```

### 4. 路由

```php
use Aphrodite\Router\AdaptiveRouter;

$router = new AdaptiveRouter();

// 基本路由
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// 命名路由
$router->get('/users', handler)->name('users.index');

// 路由分组
$router->group(['prefix' => '/api', 'middleware' => [CorsMiddleware::class]], function ($router) {
    $router->get('/users', handler);
});

// 资源路由
$router->resource('users', UserController::class);

// 处理请求
$response = $router->handle($request);
$response->send();
```

### 5. 验证器

```php
use Aphrodite\Validation\Validator;

// 基本验证
$validator = Validator::make($data, [
    'name' => 'required|min_length:2|max_length:50',
    'email' => 'required|email',
    'age' => 'numeric|min:18|max:150'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    return Response::validationError($errors);
}

// 获取验证后的数据
$validated = $validator->validated();
```

### 6. 中间件

```php
use Aphrodite\Http\Middleware\CorsMiddleware;
use Aphrodite\Http\Middleware\RateLimitMiddleware;
use Aphrodite\Http\Middleware\LoggingMiddleware;

// 使用中间件
$stack = new MiddlewareStack();
$stack->add(new CorsMiddleware());
$stack->add(new RateLimitMiddleware(maxAttempts: 60, decaySeconds: 60));
$stack->add(new LoggingMiddleware());

$response = $stack->execute($request, function ($req) {
    return Response::success(['message' => 'Hello!']);
});
```

### 7. 缓存

```php
use Aphrodite\Cache\Cache;

// 基本操作
Cache::set('key', 'value', 600); // 10分钟
$value = Cache::get('key', 'default');
$exists = Cache::has('key');
Cache::forget('key');

// 记忆模式
$data = Cache::remember('users', 300, function () {
    return User::all();
});
```

### 8. 日志

```php
use Aphrodite\Logger\Log;

// 配置日志
Log::createFileLogger('/path/to/logfile.log');

// 使用日志
Log::info('User logged in', ['user_id' => 1]);
Log::error('Something went wrong', ['error' => $e->getMessage()]);
Log::debug('Debug info', $context);
```

### 9. 队列

```php
use Aphrodite\Queue\Queue;

// 配置队列
Queue::setDriver(new ArrayQueue());

// 推送任务
Queue::push(SendEmailJob::class, ['email' => 'user@example.com']);
Queue::later(300, SendEmailJob::class, ['email' => 'user@example.com']); // 5分钟后执行

// 处理队列
Queue::work();
```

### 11. 依赖注入容器

```php
use Aphrodite\Container\Container;

$container = new Container();

// 绑定服务
$container->bind(LoggerInterface::class, function ($c) {
    return new FileLogger('/path/to/log');
});

// 单例绑定
$container->singleton(CacheInterface::class, function ($c) {
    return new FileCache('/path/to/cache');
});

// 解析服务
$logger = $container->get(LoggerInterface::class);

// 自动注入
class UserController {
    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}
}
$controller = $container->make(UserController::class);
```

### 12. ORM 关系

```php
use Aphrodite\ORM\Entity;
use Aphrodite\ORM\Attributes\BelongsTo;
use Aphrodite\ORM\Attributes\HasMany;
use Aphrodite\ORM\Attributes\BelongsToMany;

class User extends Entity
{
    #[HasMany(Post::class, foreignKey: 'user_id')]
    public function posts() { return $this->hasMany(); }
    
    #[BelongsToMany(Role::class, pivotTable: 'user_role')]
    public function roles() { return $this->belongsToMany(); }
}

class Post extends Entity
{
    #[BelongsTo(User::class, foreignKey: 'user_id')]
    public function author() { return $this->belongsTo(); }
    
    #[HasMany(Comment::class, foreignKey: 'post_id')]
    public function comments() { return $this->hasMany(); }
}

// 使用关系
$user = User::find(1);
$posts = $user->posts(); // 获取所有文章
$roles = $user->roles(); // 获取所有角色
```

### 13. 事件系统

```php
use Aphrodite\Events\TypedEvent;
use Aphrodite\Events\TypedEventDispatcher;
use Aphrodite\Events\EventSubscriberInterface;

// 定义事件
class UserRegisteredEvent extends TypedEvent
{
    public function __construct(private User $user) {}
    public function getUser(): User { return $this->user; }
}

// 监听事件
$dispatcher = new TypedEventDispatcher();
$dispatcher->addListener('user.registered', function (UserRegisteredEvent $event) {
    $user = $event->getUser();
    // 发送欢迎邮件...
});

// 触发事件
$dispatcher->dispatch(new UserRegisteredEvent($user));
```

### 14. 异常处理

```php
use Aphrodite\Exceptions\Handler;
use Aphrodite\Exceptions\ValidationException;
use Aphrodite\Exceptions\EntityNotFoundException;

$handler = new Handler();
$handler->setDebug(true);

try {
    $user = User::find($id);
    if (!$user) {
        throw new EntityNotFoundException("User not found: {$id}");
    }
} catch (ValidationException $e) {
    $response = $handler->render($e);
    // 处理验证异常
} catch (EntityNotFoundException $e) {
    $response = $handler->render($e);
    // 处理实体未找到异常
}
```

### 15. 意图解析 (AI 驱动)

```php
use Aphrodite\Engine\Parser\HybridIntentParser;
use Aphrodite\Engine\Parser\RuleBasedParser;
use Aphrodite\Engine\LLM\MockLLMClient;

// 使用规则解析器
$parser = new RuleBasedParser();
$intent = $parser->parse('Create a user with authentication and email validation');

echo $intent->getEntity();    // 'User'
echo $intent->hasFeature('authentication'); // true
echo $intent->hasFeature('email'); // true

// 使用混合解析器 (规则 + LLM)
$llmClient = new MockLLMClient();
$hybrid = new HybridIntentParser($llmClient);
$hybrid->preferLlm(); // 优先使用 LLM 结果

$intent = $hybrid->parse('Build a product catalog with search');
```

### 16. CLI 命令

```bash
# 查看帮助
php artisan help

# 创建控制器
php artisan make:controller UserController

# 创建模型
php artisan make:model User

# 创建迁移
php artisan make:migration create_users_table

# 查看路由列表
php artisan route:list
```

## 配置

### 数据库配置 (.env)

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=aphrodite
DB_USERNAME=root
DB_PASSWORD=
```

### 缓存配置

```php
use Aphrodite\Cache\Cache;
use Aphrodite\Cache\FileCache;

// 使用文件缓存
Cache::setDriver(new FileCache('/path/to/cache'));

// 使用数组缓存 (默认)
Cache::setDriver(new ArrayCache());
```

## 测试

```bash
# 运行所有测试
vendor/bin/phpunit

# 运行特定测试
vendor/bin/phpunit tests/Validation/

# 静态分析
vendor/bin/phpstan analyse
```

## 意图驱动开发

Aphrodite 的核心特性是意图驱动开发:

```php
use Aphrodite\Engine\IntentParser;
use Aphrodite\Engine\CodeGenerator;
use Aphrodite\Router\AdaptiveRouter;

// 自然语言描述意图
$description = "Create a user management system with authentication";

// 解析为结构化意图
$parser = new IntentParser();
$intent = $parser->parse($description);

// 生成代码
$generator = new CodeGenerator();
$entityCode = $generator->generateEntity($intent);
$controllerCode = $generator->generateController($intent);
$migrationCode = $generator->generateMigration($intent);

// 自动生成路由
$router = new AdaptiveRouter();
$router->generateFromIntent($intent);
```
