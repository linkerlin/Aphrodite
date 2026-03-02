<?php

declare(strict_types=1);

namespace Aphrodite\Console;

use Aphrodite\Console\Command;

/**
 * Artisan CLI application.
 */
class Artisan
{
    protected array $commands = [];
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        $this->registerDefaultCommands();
    }

    /**
     * Register default commands.
     */
    protected function registerDefaultCommands(): void
    {
        $this->register(new HelpCommand());
        $this->register(new MakeControllerCommand($this->basePath));
        $this->register(new MakeModelCommand($this->basePath));
        $this->register(new MakeMigrationCommand($this->basePath));
        $this->register(new ListRoutesCommand());
    }

    /**
     * Register a command.
     */
    public function register(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Run the CLI application.
     */
    public function run(array $argv): int
    {
        array_shift($argv);

        if (empty($argv)) {
            $this->runHelp();
            return 0;
        }

        $commandName = $argv[0];
        $args = array_slice($argv, 1);

        if (!isset($this->commands[$commandName])) {
            echo "Command '{$commandName}' not found.\n";
            return 1;
        }

        $command = $this->commands[$commandName];

        return $command->run($this->parseArgs($args));
    }

    /**
     * Parse command arguments.
     */
    protected function parseArgs(array $args): array
    {
        $parsed = ['options' => [], 'arguments' => []];
        $current = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $current = substr($arg, 2);
                $parsed['options'][$current] = true;
            } elseif (str_starts_with($arg, '-')) {
                $current = substr($arg, 1);
                $parsed['options'][$current] = true;
            } elseif ($current !== null) {
                $parsed['options'][$current] = $arg;
                $current = null;
            } else {
                $parsed['arguments'][] = $arg;
            }
        }

        return $parsed;
    }

    /**
     * Run help command.
     */
    protected function runHelp(): void
    {
        $this->commands['help']->run(['arguments' => []]);
    }

    /**
     * Get all registered commands.
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}

/**
 * Command interface.
 */
interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function run(array $args): int;
}

/**
 * Abstract command base class.
 */
abstract class BaseCommand implements CommandInterface
{
    protected string $name;
    protected string $description;
    protected array $options = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function option(string $key, array $args, mixed $default = null): mixed
    {
        return $args['options'][$key] ?? $default;
    }

    protected function argument(int $index, array $args, mixed $default = null): mixed
    {
        return $args['arguments'][$index] ?? $default;
    }

    protected function info(string $message): void
    {
        echo "\033[32mINFO:\033[0m {$message}\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31mERROR:\033[0m {$message}\n";
    }

    protected function warn(string $message): void
    {
        echo "\033[33mWARNING:\033[0m {$message}\n";
    }

    protected function line(string $message): void
    {
        echo "{$message}\n";
    }
}
