# Aphrodite API 参考

## 容器

### Container (PSR-11)

```php
class Container implements ContainerInterface
{
    // 绑定服务
    public function bind(string $id, callable $concrete): self;
    public function singleton(string $id, callable $concrete): self;
    public function instance(string $id, mixed $instance): self;
    
    // 解析服务
    public function get(string $id): mixed;
    public function has(string $id): bool;
    public function make(string $id, array $parameters = []): mixed;
    
    // 服务提供者
    public function register(ServiceProviderInterface $provider): self;
    public function boot(): self;
    
    // 自动注入
    public function call(callable $callback, array $parameters = []): mixed;
}
```

### Application

```php
class Application extends Container
{
    public static function getInstance(): self;
    
    // 生命周期
    public function bootstrap(): self;
    public function run(): void;
    
    // 环境检测
    public function environment(): string;
    public function isLocal(): bool;
    public function isProduction(): bool;
}
```

## ORM

### Entity

```php
abstract class Entity
{
    // 表名
    protected static function getTable(): string;
    
    // 主键
    protected static function getPrimaryKey(): string;
    
    // 查询构建器
    public static function query(): QueryBuilder;
    
    // 查找
    public static function find(int $id): ?static;
    public static function findOrFail(int $id): static;
    public static function all(): array;
    public static function where(string $column, mixed $operator, mixed $value = null): array;
    public static function firstWhere(string $column, mixed $operator, mixed $value = null): ?static;
    public static function create(array $attributes): static;
    
    // 保存/删除
    public function save(): bool;
    public function delete(): bool;
    
    // 状态检查
    public function isDirty(): bool;
    public function isExists(): bool;
    
    // 属性访问
    public function toArray(): array;
    public function fill(array $attributes): static;
    public static function fromArray(array $data): static;
}
```

### QueryBuilder

```php
class QueryBuilder
{
    // 创建
    public static function table(PDO $pdo, string $table): self;
    
    // 查询
    public function select(array|string $columns): self;
    public function where(string $column, mixed $operator, mixed $value = null): self;
    public function orWhere(string $column, mixed $operator, mixed $value = null): self;
    public function whereIn(string $column, array $values): self;
    public function whereNull(string $column): self;
    public function orderBy(string $column, string $direction = 'ASC'): self;
    public function limit(int $limit): self;
    public function offset(int $offset): self;
    
    // 执行
    public function get(): array;
    public function first(): ?array;
    public function find(int $id): ?array;
    public function insert(): int;
    public function update(): int;
    public function delete(): int;
    public function count(): int;
    public function exists(): bool;
}
```

### ORM 关系

```php
// BelongsTo - 属于关系 (Post 属于 User)
class BelongsTo extends Relation
{
    public function getResults(): ?Entity;
}

// HasMany - 一对多关系 (User 有多个 Post)
class HasMany extends Relation
{
    public function getResults(): array;
}

// HasOne - 一对一关系 (User 有一个 Profile)
class HasOne extends Relation
{
    public function getResults(): ?Entity;
}

// BelongsToMany - 多对多关系 (User 属于多个 Role)
class BelongsToMany extends Relation
{
    public function getResults(): array;
    public function attach(int $id, array $pivot = []): void;
    public function detach(int $id): void;
    public function sync(array $ids): void;
}
```

### 关系属性

```php
use Aphrodite\ORM\Attributes\BelongsTo;
use Aphrodite\ORM\Attributes\HasMany;
use Aphrodite\ORM\Attributes\HasOne;
use Aphrodite\ORM\Attributes\BelongsToMany;

class User extends Entity
{
    #[HasMany(Post::class, foreignKey: 'user_id')]
    public function posts() { return $this->hasMany(); }
    
    #[HasOne(Profile::class, foreignKey: 'user_id')]
    public function profile() { return $this->hasOne(); }
}

class Post extends Entity
{
    #[BelongsTo(User::class, foreignKey: 'user_id')]
    public function author() { return $this->belongsTo(); }
    
    #[BelongsToMany(Tag::class, pivotTable: 'post_tag')]
    public function tags() { return $this->belongsToMany(); }
}
```

## HTTP

### Request

```php
class Request
{
    public static function capture(): self;
    
    // 请求信息
    public function getMethod(): string;
    public function getUri(): string;
    public function getPath(): string;
    
    // 输入
    public function get(string $key, mixed $default = null): mixed;
    public function getQuery(): array;
    public function post(string $key, mixed $default = null): mixed;
    public function getPost(): array;
    public function input(string $key, mixed $default = null): mixed;
    public function all(): array;
    public function has(string $key): bool;
    public function only(array $keys): array;
    public function except(array $keys): array;
    public function getJson(): ?array;
    
    // Headers
    public function header(string $key, ?string $default = null): ?string;
    public function getHeaders(): array;
    
    // 请求类型
    public function isGet(): bool;
    public function isPost(): bool;
    public function isPut(): bool;
    public function isDelete(): bool;
    public function isPatch(): bool;
    public function isAjax(): bool;
    
    // 客户端信息
    public function ip(): ?string;
    public function userAgent(): ?string;
    
    // 属性
    public function setAttribute(string $key, mixed $value): self;
    public function getAttribute(string $key, mixed $default = null): mixed;
}
```

### Response

```php
class Response
{
    // 创建
    public static function make(mixed $content = '', int $statusCode = 200, array $headers = []): self;
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self;
    public static function success(mixed $data = null, ?string $message = 'Success', int $statusCode = 200): self;
    public static function error(mixed $message = 'Error', int $statusCode = 400, ?array $errors = null): self;
    public static function notFound(?string $message = 'Resource not found'): self;
    public static function unauthorized(?string $message = 'Unauthorized'): self;
    public static function forbidden(?string $message = 'Forbidden'): self;
    public static function validationError(array $errors, ?string $message = 'Validation failed'): self;
    public static function serverError(?string $message = 'Internal server error'): self;
    public static function redirect(string $url, int $statusCode = 302): self;
    public static function download(string $filePath, ?string $name = null): self;
    
    // 内容
    public function getContent(): mixed;
    public function setContent(mixed $content): self;
    
    // 状态
    public function getStatusCode(): int;
    public function setStatusCode(int $statusCode): self;
    
    // Headers
    public function getHeader(string $key): ?string;
    public function setHeader(string $key, string $value): self;
    public function getHeaders(): array;
    
    // 发送
    public function send(): void;
}
```

### MiddlewareStack

```php
class MiddlewareStack
{
    public function add(MiddlewareInterface|string $middleware): self;
    public function addMany(array $middleware): self;
    public function prepend(MiddlewareInterface $middleware): self;
    public function all(): array;
    public function execute(Request $request, callable $finalHandler): Response;
}
```

### MiddlewareInterface

```php
interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}
```

## 异常处理

### Exception Hierarchy

```php
// 基类
abstract class AphroditeException extends Exception
{
    protected array $context = [];
    public function getContext(): array;
    public function setContext(array $context): self;
}

// 具体异常
class ValidationException extends AphroditeException {}
class EntityNotFoundException extends AphroditeException {}
class RouteNotFoundException extends AphroditeException {}
class AuthenticationException extends AphroditeException {}
class AuthorizationException extends AphroditeException {}
```

### Exception Handler

```php
class Handler
{
    public function report(Throwable $e): void;
    public function render(Throwable $e, Request $request = null): Response;
    public function shouldReport(Throwable $e): bool;
    public function setDebug(bool $debug): self;
}
```

## 事件系统

### TypedEvent

```php
abstract class TypedEvent
{
    public function getName(): string;
    public function getPayload(): mixed;
    public function stopPropagation(): void;
    public function isPropagationStopped(): bool;
}
```

### TypedEventDispatcher

```php
class TypedEventDispatcher
{
    public function addListener(string $eventName, callable $listener, int $priority = 0): self;
    public function removeListener(string $eventName, callable $listener): self;
    public function hasListeners(string $eventName): bool;
    public function dispatch(TypedEvent $event): TypedEvent;
    public function addSubscriber(EventSubscriberInterface $subscriber): self;
}
```

### EventSubscriberInterface

```php
interface EventSubscriberInterface
{
    public static function getSubscribedEvents(): array;
}
```

## 验证

### Validator

```php
class Validator
{
    public static function make(array $data, array $rules, array $messages = []): self;
    public static function fromRequest(Request $request, array $rules, array $messages = []): self;
    
    public function validate(): bool;
    public function passes(): bool;
    public function fails(): bool;
    public function errors(): array;
    public function firstError(string $field): ?string;
    public function firstErrors(): ?string;
    public function hasError(string $field): bool;
    public function validated(): array;
    public function addRule(string $field, string|array $rule): self;
    public function sometimes(string $field, string|array $rules, callable $condition): self;
}
```

### 内置验证规则

- `required` - 必填
- `email` - 邮箱格式
- `min_length:N` - 最小长度
- `max_length:N` - 最大长度
- `min:N` - 最小值
- `max:N` - 最大值
- `numeric` - 数字
- `integer` - 整数
- `alpha` - 仅字母
- `alpha_num` - 字母和数字
- `regex:pattern` - 正则
- `in:value1,value2,...` - 枚举
- `url` - URL格式
- `date_format:format` - 日期格式
- `confirmed` - 确认字段

## 路由

### AdaptiveRouter

```php
class AdaptiveRouter
{
    // 注册路由
    public function addRoute(string $method, string $path, callable|array $handler): self;
    public function get(string $path, callable|array $handler): self;
    public function post(string $path, callable|array $handler): self;
    public function put(string $path, callable|array $handler): self;
    public function patch(string $path, callable|array $handler): self;
    public function delete(string $path, callable|array $handler): self;
    public function any(string $path, callable|array $handler): self;
    public function mapMethods(array $methods, string $path, callable|array $handler): self;
    
    // 路由功能
    public function name(string $name): self;
    public function middleware(array|string $middleware): self;
    public function group(array $config, callable $callback): void;
    public function resource(string $name, string $controller): self;
    public function notFound(callable $handler): self;
    public function getUrl(string $name, array $params = []): ?string;
    
    // 匹配
    public function match(string $method, string $path): ?array;
    public function handle(Request $request): Response;
    
    // 优化
    public function optimize(): void;
    public function getRoutes(): array;
    public function count(): int;
    
    // 意图驱动
    public function generateFromIntent(array $intent): void;
}
```

## 缓存

### Cache

```php
class Cache
{
    public static function getDriver(): CacheInterface;
    public static function setDriver(CacheInterface $driver): void;
    public static function setPrefix(string $prefix): void;
    
    public static function get(string $key, mixed $default = null): mixed;
    public static function set(string $key, mixed $value, int $ttl = 0): bool;
    public static function has(string $key): bool;
    public static function forget(string $key): bool;
    public static function flush(): bool;
    public static function remember(string $key, int $ttl, callable $callback): mixed;
}
```

### CacheInterface

```php
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = 0): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
}
```

## 日志

### Log

```php
class Log
{
    public static function getLogger(): LoggerInterface;
    public static function setLogger(LoggerInterface $logger): void;
    public static function createFileLogger(string $path, ?int $minLevel = null): FileLogger;
    public static function createDailyLogger(string $path, ?int $minLevel = null): DailyLogger;
    public static function createConsoleLogger(?int $minLevel = null): ConsoleLogger;
    
    public static function log(string $level, string $message, array $context = []): void;
    public static function debug(string $message, array $context = []): void;
    public static function info(string $message, array $context = []): void;
    public static function notice(string $message, array $context = []): void;
    public static function warning(string $message, array $context = []): void;
    public static function error(string $message, array $context = []): void;
    public static function critical(string $message, array $context = []): void;
    public static function alert(string $message, array $context = []): void;
    public static function emergency(string $message, array $context = []): void;
}
```

## 队列

### Queue

```php
class Queue
{
    public static function getDriver(): QueueInterface;
    public static function setDriver(QueueInterface $driver): void;
    public static function setDefaultQueue(string $queue): void;
    
    public static function push(string $job, array $data = [], ?string $queue = null): string;
    public static function later(int $delay, string $job, array $data = [], ?string $queue = null): string;
    public static function dispatchSync(string $job, array $data = []): void;
    public static function pop(?string $queue = null): ?QueuedJob;
    public static function process(?string $queue = null): bool;
    public static function work(?string $queue = null, int $maxJobs = 0): void;
}
```

## 配置

### Config

```php
class Config
{
    public static function load(array $config): void;
    public static function loadFile(string $path): void;
    public static function get(string $key, mixed $default = null): mixed;
    public static function set(string $key, mixed $value): void;
    public static function has(string $key): bool;
    public static function all(): array;
    public static function clear(): void;
}
```

### Environment

```php
class Environment
{
    public static function load(string $path = null): void;
    public static function get(string $key, mixed $default = null): mixed;
    public static function has(string $key): bool;
    public static function set(string $key, mixed $value): void;
    public static function getEnv(): string;
    public static function is(string $env): bool;
    public static function isDevelopment(): bool;
    public static function isProduction(): bool;
}
```

### ConfigSchema

```php
class ConfigSchema
{
    public function define(string $key, string $type, mixed $default = null, bool $required = false): self;
    public function defineString(string $key, ?string $default = null, bool $required = false): self;
    public function defineInt(string $key, ?int $default = null, bool $required = false): self;
    public function defineBool(string $key, ?bool $default = null, bool $required = false): self;
    public function defineArray(string $key, ?array $default = null, bool $required = false): self;
    public function getDefinition(string $key): ?array;
    public function getDefaults(): array;
}
```

### ConfigValidator

```php
class ConfigValidator
{
    public function __construct(ConfigSchema $schema);
    
    public function validate(array $config): bool;
    public function errors(): array;
    public function getValidated(): array;
    public function addCustomRule(string $name, callable $rule): self;
}
```

## 引擎

### Intent (值对象)

```php
class Intent
{
    // 创建
    public function __construct(
        ?string $entity = null,
        array $features = [],
        array $constraints = [],
        array $operations = [],
        array $metadata = []
    );
    public static function empty(): self;
    public static function fromArray(array $data): self;
    
    // 访问
    public function getEntity(): ?string;
    public function hasEntity(): bool;
    public function getFeatures(): array;
    public function hasFeature(string $feature): bool;
    public function getOperations(): array;
    public function hasOperation(string $operation): bool;
    public function getConstraints(): array;
    public function hasConstraint(string $key): bool;
    public function getConstraint(string $key, mixed $default = null): mixed;
    public function getMetadata(): array;
    public function getMeta(string $key, mixed $default = null): mixed;
    
    // 修改 (返回新实例)
    public function withFeature(string $feature): self;
    public function withOperation(string $operation): self;
    public function withConstraint(string $key, mixed $value): self;
    public function merge(Intent $other): self;
    
    // 工具
    public function isEmpty(): bool;
    public function toArray(): array;
}
```

### IntentParserInterface

```php
interface IntentParserInterface
{
    public function parse(string $description): Intent;
    public function canParse(string $description): bool;
    public function getName(): string;
}
```

### RuleBasedParser

```php
class RuleBasedParser implements IntentParserInterface
{
    public function parse(string $description): Intent;
    public function canParse(string $description): bool;
    public function getName(): string; // 'rule-based'
}
```

### HybridIntentParser

```php
class HybridIntentParser implements IntentParserInterface
{
    public function __construct(?LLMClientInterface $llmClient = null);
    
    public function setLlmClient(LLMClientInterface $client): self;
    public function setLlmThreshold(float $threshold): self;
    public function preferLlm(bool $prefer = true): self;
    
    public function parse(string $description): Intent;
    public function canParse(string $description): bool;
    public function getName(): string; // 'hybrid'
}
```

### LLMClientInterface

```php
interface LLMClientInterface
{
    public function parseIntent(string $description): array;
    public function generateCode(string $prompt, array $context = []): string;
    public function completeCode(string $code, array $options = []): string;
    public function getModelName(): string;
    public function isAvailable(): bool;
    public function getLastError(): ?string;
}
```

### 代码定义

```php
class ClassDefinition
{
    public function __construct(string $name);
    public function setNamespace(string $namespace): self;
    public function setExtends(string $class): self;
    public function addImplements(string $interface): self;
    public function addTrait(string $trait): self;
    public function addProperty(PropertyDefinition $property): self;
    public function addMethod(MethodDefinition $method): self;
    public function addAttribute(string $attribute, array $args = []): self;
    public function render(): string;
}

class MethodDefinition
{
    public function __construct(string $name);
    public function setVisibility(string $visibility): self; // public|protected|private
    public function setStatic(bool $static = true): self;
    public function setReturnType(string $type): self;
    public function addParameter(string $name, string $type = '', mixed $default = null): self;
    public function setBody(string $body): self;
    public function render(): string;
}

class PropertyDefinition
{
    public function __construct(string $name);
    public function setVisibility(string $visibility): self;
    public function setStatic(bool $static = true): self;
    public function setType(string $type): self;
    public function setDefault(mixed $default): self;
    public function render(): string;
}
```

### CodeGenerator

```php
class CodeGenerator
{
    public function generateEntity(array $intent): string;
    public function generateController(array $intent): string;
    public function generateMigration(array $intent): string;
}
```

### SchemaEvolver

```php
class SchemaEvolver
{
    public function evolve(array $currentSchema, array $newIntent): array;
    public function generateMigration(array $operations): string;
    public function detectBreakingChanges(array $operations): array;
}
```

### TestSynthesizer

```php
class TestSynthesizer
{
    public function synthesize(array $intent): string;
    public function synthesizeFromEntity(array $entityDefinition): string;
}
```

## CLI

### Artisan

```bash
# 帮助
php artisan help

# 创建
php artisan make:controller UserController
php artisan make:model User
php artisan make:migration create_users_table

# 路由
php artisan route:list
```
