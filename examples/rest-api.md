# Aphrodite REST API 示例

这是一个完整的 REST API 示例，展示如何使用 Aphrodite 框架构建现代 API。

## 项目结构

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApiController.php
│   │   │   ├── UserController.php
│   │   │   └── ProductController.php
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php
│   │       └── RateLimitMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Product.php
│   └── Services/
│       └── AuthService.php
├── config/
│   ├── app.php
│   └── database.php
├── public/
│   └── index.php
├── routes/
│   └── api.php
├── storage/
│   ├── cache/
│   └── logs/
└── .env
```

## 核心代码

### 基础控制器

```php
<?php

namespace App\Http\Controllers;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

abstract class ApiController
{
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): Response
    {
        return Response::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, ?array $errors = null): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function paginate(array $items, int $total, int $page, int $perPage): Response
    {
        return $this->success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
    }
}
```

### User 模型

```php
<?php

namespace App\Models;

use Aphrodite\ORM\Entity;
use Aphrodite\ORM\Attributes\HasMany;

class User extends Entity
{
    protected static function getTable(): string
    {
        return 'users';
    }

    protected static function getPrimaryKey(): string
    {
        return 'id';
    }

    protected array $fillable = ['name', 'email', 'password'];

    protected array $hidden = ['password'];

    #[HasMany(Product::class, foreignKey: 'user_id')]
    public function products()
    {
        return $this->hasMany();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public static function findByEmail(string $email): ?static
    {
        return static::firstWhere('email', '=', $email);
    }
}
```

### Product 模型

```php
<?php

namespace App\Models;

use Aphrodite\ORM\Entity;
use Aphrodite\ORM\Attributes\BelongsTo;

class Product extends Entity
{
    protected static function getTable(): string
    {
        return 'products';
    }

    protected array $fillable = ['name', 'description', 'price', 'user_id'];

    #[BelongsTo(User::class, foreignKey: 'user_id')]
    public function owner()
    {
        return $this->belongsTo();
    }
}
```

### 认证服务

```php
<?php

namespace App\Services;

use App\Models\User;
use Aphrodite\Exceptions\AuthenticationException;

class AuthService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET') ?: 'your-secret-key';
    }

    public function login(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        return [
            'user' => $user->toArray(),
            'token' => $this->generateToken($user),
        ];
    }

    public function register(array $data): User
    {
        $existingUser = User::findByEmail($data['email']);
        
        if ($existingUser) {
            throw new AuthenticationException('Email already exists');
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        return User::create($data);
    }

    public function validateToken(string $token): ?array
    {
        // 简化的 JWT 验证 (生产环境应使用 firebase/php-jwt)
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        
        if ($payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function generateToken(User $user): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'user_id' => $user->id,
            'email' => $user->email,
            'exp' => time() + 3600,
        ]));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $this->secretKey, true));

        return "{$header}.{$payload}.{$signature}";
    }
}
```

### 认证中间件

```php
<?php

namespace App\Http\Middleware;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Http\MiddlewareInterface;
use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function process(Request $request, callable $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return Response::unauthorized('Missing or invalid authorization header');
        }

        $token = substr($authHeader, 7);
        $payload = $this->auth->validateToken($token);

        if (!$payload) {
            return Response::unauthorized('Invalid or expired token');
        }

        $request->setAttribute('user_id', $payload['user_id']);
        $request->setAttribute('user_email', $payload['email']);

        return $next($request);
    }
}
```

### UserController

```php
<?php

namespace App\Http\Controllers;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Validation\Validator;
use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\AuthService;

class UserController extends ApiController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function register(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min_length:2|max_length:100',
            'email' => 'required|email',
            'password' => 'required|min_length:8',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $this->auth->register($validator->validated());

        return $this->success($user->toArray(), 'User registered successfully', 201);
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $this->auth->login(
            $request->input('email'),
            $request->input('password')
        );

        return $this->success($data, 'Login successful');
    }

    public function profile(Request $request): Response
    {
        $user = User::find($request->getAttribute('user_id'));

        if (!$user) {
            return $this->error('User not found', 404);
        }

        return $this->success($user->toArray());
    }

    public function update(Request $request): Response
    {
        $user = User::find($request->getAttribute('user_id'));

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'min_length:2|max_length:100',
            'email' => 'email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user->fill($validator->validated());
        $user->save();

        return $this->success($user->toArray(), 'Profile updated successfully');
    }
}
```

### ProductController

```php
<?php

namespace App\Http\Controllers;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Validation\Validator;
use App\Http\Controllers\ApiController;
use App\Models\Product;

class ProductController extends ApiController
{
    public function index(Request $request): Response
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $offset = ($page - 1) * $perPage;

        $products = Product::query()
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $total = Product::query()->count();

        $items = array_map(fn($p) => (new Product())->fill($p)->toArray(), $products);

        return $this->paginate($items, $total, $page, $perPage);
    }

    public function show(Request $request, array $params): Response
    {
        $product = Product::find((int) $params['id']);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product->toArray());
    }

    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min_length:2|max_length:255',
            'description' => 'required|min_length:10',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $validator->validated();
        $data['user_id'] = $request->getAttribute('user_id');

        $product = Product::create($data);

        return $this->success($product->toArray(), 'Product created successfully', 201);
    }

    public function update(Request $request, array $params): Response
    {
        $product = Product::find((int) $params['id']);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        // 检查所有权
        if ($product->user_id !== $request->getAttribute('user_id')) {
            return Response::forbidden('You do not own this product');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'min_length:2|max_length:255',
            'description' => 'min_length:10',
            'price' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product->fill($validator->validated());
        $product->save();

        return $this->success($product->toArray(), 'Product updated successfully');
    }

    public function destroy(Request $request, array $params): Response
    {
        $product = Product::find((int) $params['id']);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        // 检查所有权
        if ($product->user_id !== $request->getAttribute('user_id')) {
            return Response::forbidden('You do not own this product');
        }

        $product->delete();

        return $this->success(['deleted' => true], 'Product deleted successfully');
    }
}
```

### 路由配置

```php
<?php

use Aphrodite\Router\AdaptiveRouter;
use Aphrodite\Http\Middleware\CorsMiddleware;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\AuthMiddleware;

$router = new AdaptiveRouter();

// CORS 中间件
$router->group(['middleware' => [CorsMiddleware::class]], function ($router) {
    
    // 公开路由
    $router->post('/auth/register', [UserController::class, 'register']);
    $router->post('/auth/login', [UserController::class, 'login']);
    
    // 公开 API
    $router->get('/products', [ProductController::class, 'index']);
    $router->get('/products/{id}', [ProductController::class, 'show']);
    
    // 需要认证的路由
    $router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
        
        // 用户
        $router->get('/user/profile', [UserController::class, 'profile']);
        $router->put('/user/profile', [UserController::class, 'update']);
        
        // 产品 (需要认证的操作)
        $router->post('/products', [ProductController::class, 'store']);
        $router->put('/products/{id}', [ProductController::class, 'update']);
        $router->delete('/products/{id}', [ProductController::class, 'destroy']);
    });
});

return $router;
```

### 入口文件

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Logger\Log;
use Aphrodite\Exceptions\Handler;

// 加载环境变量
Aphrodite\Config\Environment::load(__DIR__ . '/../.env');

// 初始化日志
Log::createFileLogger(__DIR__ . '/../storage/logs/api.log');

// 设置异常处理器
$handler = new Handler();
$handler->setDebug(getenv('APP_DEBUG') === 'true');

try {
    // 捕获请求
    $request = Request::capture();
    
    // 加载路由
    $router = require __DIR__ . '/../routes/api.php';
    
    // 处理请求
    $response = $router->handle($request);
    
} catch (Throwable $e) {
    $response = $handler->render($e, $request ?? null);
}

// 发送响应
$response->send();
```

## API 端点

### 认证

| 方法 | 端点 | 描述 | 认证 |
|------|------|------|------|
| POST | `/auth/register` | 注册用户 | 否 |
| POST | `/auth/login` | 登录获取 token | 否 |

### 用户

| 方法 | 端点 | 描述 | 认证 |
|------|------|------|------|
| GET | `/user/profile` | 获取当前用户 | 是 |
| PUT | `/user/profile` | 更新当前用户 | 是 |

### 产品

| 方法 | 端点 | 描述 | 认证 |
|------|------|------|------|
| GET | `/products` | 产品列表 (分页) | 否 |
| GET | `/products/{id}` | 获取单个产品 | 否 |
| POST | `/products` | 创建产品 | 是 |
| PUT | `/products/{id}` | 更新产品 | 是 |
| DELETE | `/products/{id}` | 删除产品 | 是 |

## 使用示例

### 注册

```bash
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

响应:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### 登录

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

响应:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### 获取产品列表

```bash
curl http://localhost:8080/products?page=1&per_page=10
```

响应:
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Product 1",
        "description": "Description...",
        "price": 99.99,
        "user_id": 1
      }
    ],
    "pagination": {
      "total": 100,
      "page": 1,
      "per_page": 10,
      "total_pages": 10
    }
  }
}
```

### 创建产品 (需要认证)

```bash
curl -X POST http://localhost:8080/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "New Product",
    "description": "A new product description",
    "price": 49.99
  }'
```

响应:
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 2,
    "name": "New Product",
    "description": "A new product description",
    "price": 49.99,
    "user_id": 1
  }
}
```

### 更新产品

```bash
curl -X PUT http://localhost:8080/products/2 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Updated Product",
    "price": 39.99
  }'
```

### 删除产品

```bash
curl -X DELETE http://localhost:8080/products/2 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 数据库迁移

```sql
-- 用户表
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 产品表
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 运行

```bash
# 安装依赖
composer install

# 配置环境
cp .env.example .env
# 编辑 .env 文件

# 创建数据库
mysql -u root -p < database/schema.sql

# 启动服务器
php -S localhost:8080 -t public

# 或使用 Artisan
php artisan serve --port=8080
```

## 测试

```bash
# 运行所有测试
vendor/bin/phpunit

# 运行特定测试
vendor/bin/phpunit tests/Feature/ApiTest.php
```
