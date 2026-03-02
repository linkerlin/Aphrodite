<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Logger;

require_once __DIR__ . '/../../src/Logger/Logger.php';

use Aphrodite\Logger\Log;
use Aphrodite\Logger\FileLogger;
use Aphrodite\Logger\ConsoleLogger;
use Aphrodite\Logger\Level;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset logger
        Log::setLogger(new ConsoleLogger());
    }

    public function testLogLevels(): void
    {
        $logger = new class extends \Aphrodite\Logger\AbstractLogger {
            public array $logged = [];
            
            protected function writeLog(\Aphrodite\Logger\LogEntry $entry): void
            {
                $this->logged[] = $entry;
            }
        };

        Log::setLogger($logger);

        Log::debug('debug message');
        Log::info('info message');
        Log::warning('warning message');
        Log::error('error message');

        $this->assertCount(4, $logger->logged);
        $this->assertEquals('debug', $logger->logged[0]->level);
        $this->assertEquals('error', $logger->logged[3]->level);
    }

    public function testLogWithContext(): void
    {
        $logger = new class extends \Aphrodite\Logger\AbstractLogger {
            public array $logged = [];
            
            protected function writeLog(\Aphrodite\Logger\LogEntry $entry): void
            {
                $this->logged[] = $entry;
            }
        };

        Log::setLogger($logger);

        Log::info('User logged in', ['user_id' => 123, 'ip' => '192.168.1.1']);

        $this->assertEquals(['user_id' => 123, 'ip' => '192.168.1.1'], $logger->logged[0]->context);
    }

    public function testLevelPriorities(): void
    {
        $this->assertEquals(100, Level::priority(Level::DEBUG));
        $this->assertEquals(200, Level::priority(Level::INFO));
        $this->assertEquals(300, Level::priority(Level::WARNING));
        $this->assertEquals(400, Level::priority(Level::ERROR));
        $this->assertEquals(500, Level::priority(Level::CRITICAL));
    }

    public function testAllLevels(): void
    {
        $levels = Level::all();

        $this->assertContains('debug', $levels);
        $this->assertContains('info', $levels);
        $this->assertContains('warning', $levels);
        $this->assertContains('error', $levels);
        $this->assertContains('critical', $levels);
    }
}

class FileLoggerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/aphrodite_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testWriteLog(): void
    {
        $logger = new FileLogger($this->logFile);
        $logger->info('Test message');

        $this->assertFileExists($this->logFile);
        
        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringContainsString('INFO', $content);
    }

    public function testLogWithContext(): void
    {
        $logger = new FileLogger($this->logFile);
        $logger->info('User action', ['user_id' => 123]);

        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('user_id', $content);
    }

    public function testMultipleLogLevels(): void
    {
        $logger = new FileLogger($this->logFile);
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->error('Error message');

        $content = file_get_contents($this->logFile);
        
        $this->assertStringContainsString('DEBUG', $content);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('ERROR', $content);
    }

    public function testCreatesDirectory(): void
    {
        $logFile = sys_get_temp_dir() . '/nested/dir/test.log';
        $logger = new FileLogger($logFile);
        $logger->info('Test');

        $this->assertFileExists($logFile);
        
        // Cleanup
        unlink($logFile);
        rmdir(dirname($logFile));
        rmdir(dirname(dirname($logFile)));
    }
}

class ConsoleLoggerTest extends TestCase
{
    public function testCreateConsoleLogger(): void
    {
        $logger = new ConsoleLogger();

        $this->assertInstanceOf(ConsoleLogger::class, $logger);
    }

    public function testColoredOutput(): void
    {
        $logger = new ConsoleLogger(Level::DEBUG, true);

        // Just ensure it can be created without errors
        $this->assertInstanceOf(ConsoleLogger::class, $logger);
    }

    public function testNoColoredOutput(): void
    {
        $logger = new ConsoleLogger(Level::DEBUG, false);

        $this->assertInstanceOf(ConsoleLogger::class, $logger);
    }
}
