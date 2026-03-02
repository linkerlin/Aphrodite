<?php

declare(strict_types=1);

namespace Aphrodite\Console;

use Aphrodite\Console\BaseCommand;

/**
 * Help command.
 */
class HelpCommand extends BaseCommand
{
    protected string $name = 'help';
    protected string $description = 'Display help information';

    public function run(array $args): int
    {
        echo "\nAphrodite CLI\n";
        echo str_repeat('=', 50) . "\n\n";

        echo "Available commands:\n\n";

        $commands = [
            'help' => 'Display help information',
            'make:controller' => 'Create a new controller',
            'make:model' => 'Create a new model',
            'make:migration' => 'Create a new migration',
            'route:list' => 'List all registered routes',
        ];

        foreach ($commands as $name => $desc) {
            echo "  {$name}\n";
            echo "    {$desc}\n\n";
        }

        echo "\nUsage: php artisan [command] [options]\n";
        echo "Example: php artisan make:controller UserController\n\n";

        return 0;
    }
}

/**
 * Make Controller command.
 */
class MakeControllerCommand extends BaseCommand
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller';
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function run(array $args): int
    {
        $name = $this->argument(0, $args);

        if (!$name) {
            $this->error('Controller name is required.');
            return 1;
        }

        $name = str_replace('Controller', '', $name) . 'Controller';
        $path = $this->basePath . '/app/Http/Controllers/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Controller '{$name}' already exists.");
            return 1;
        }

        $this->ensureDirectory(dirname($path));

        $template = $this->getTemplate($name);

        file_put_contents($path, $template);

        $this->info("Controller created: {$name}");

        return 0;
    }

    protected function getTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

class {$name}
{
    public function index(Request \$request): Response
    {
        return Response::success([]);
    }

    public function show(Request \$request, array \$params): Response
    {
        \$id = \$params['id'] ?? null;
        return Response::success(['id' => \$id]);
    }

    public function store(Request \$request): Response
    {
        \$data = \$request->all();
        return Response::success(\$data, 'Created successfully', 201);
    }

    public function update(Request \$request, array \$params): Response
    {
        \$id = \$params['id'] ?? null;
        \$data = \$request->all();
        return Response::success(array_merge(\$data, ['id' => \$id]));
    }

    public function destroy(Request \$request, array \$params): Response
    {
        \$id = \$params['id'] ?? null;
        return Response::success(['deleted' => true]);
    }
}
PHP;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

/**
 * Make Model command.
 */
class MakeModelCommand extends BaseCommand
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model';
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function run(array $args): int
    {
        $name = $this->argument(0, $args);

        if (!$name) {
            $this->error('Model name is required.');
            return 1;
        }

        $path = $this->basePath . '/app/Models/' . $name . '.php';

        if (file_exists($path)) {
            $this->error("Model '{$name}' already exists.");
            return 1;
        }

        $this->ensureDirectory(dirname($path));

        $template = $this->getTemplate($name);

        file_put_contents($path, $template);

        $this->info("Model created: {$name}");

        return 0;
    }

    protected function getTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Aphrodite\ORM\Entity;

class {$name} extends Entity
{
    protected static function getTable(): string
    {
        return strtolower('{$name}s');
    }
}
PHP;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

/**
 * Make Migration command.
 */
class MakeMigrationCommand extends BaseCommand
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new migration';
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function run(array $args): int
    {
        $name = $this->argument(0, $args);

        if (!$name) {
            $this->error('Migration name is required.');
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';
        $path = $this->basePath . '/database/migrations/' . $filename;

        $this->ensureDirectory(dirname($path));

        $template = $this->getTemplate($name);

        file_put_contents($path, $template);

        $this->info("Migration created: {$filename}");

        return 0;
    }

    protected function getTemplate(string $name): string
    {
        $table = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name));

        return <<<PHP
<?php

declare(strict_types=1);

use Aphrodite\Database\Schema;
use Aphrodite\Database\Blueprint;

return new class
{
    public function up(Schema \$schema): void
    {
        \$schema->create('{$table}s', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(Schema \$schema): void
    {
        \$schema->dropIfExists('{$table}s');
    }
};
PHP;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

/**
 * List Routes command.
 */
class ListRoutesCommand extends BaseCommand
{
    protected string $name = 'route:list';
    protected string $description = 'List all registered routes';

    public function run(array $args): int
    {
        echo "\nRegistered Routes\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "Note: Routes need to be registered in the application.\n";
        echo "Use \$router->get(), \$router->post(), etc. to add routes.\n\n";

        return 0;
    }
}
