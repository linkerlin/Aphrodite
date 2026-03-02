<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Console;

require_once __DIR__ . '/../../src/Console/Artisan.php';
require_once __DIR__ . '/../../src/Console/Commands.php';

use Aphrodite\Console\Artisan;
use Aphrodite\Console\HelpCommand;
use Aphrodite\Console\MakeControllerCommand;
use Aphrodite\Console\MakeModelCommand;
use Aphrodite\Console\MakeMigrationCommand;
use Aphrodite\Console\ListRoutesCommand;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    private string $testPath;

    protected function setUp(): void
    {
        $this->testPath = sys_get_temp_dir() . '/aphrodite_console_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testPath)) {
            $this->recursiveDelete($this->testPath);
        }
    }

    private function recursiveDelete(string $path): void
    {
        if (is_dir($path)) {
            foreach (glob($path . '/*') as $file) {
                $this->recursiveDelete($file);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    public function testArtisanCanRegisterCommands(): void
    {
        $artisan = new Artisan();
        
        $this->assertInstanceOf(Artisan::class, $artisan);
    }

    public function testHelpCommand(): void
    {
        $command = new HelpCommand();
        
        $this->assertEquals('help', $command->getName());
        $this->assertEquals('Display help information', $command->getDescription());
    }

    public function testMakeControllerCommandName(): void
    {
        $command = new MakeControllerCommand('/tmp');
        
        $this->assertEquals('make:controller', $command->getName());
    }

    public function testMakeControllerRequiresName(): void
    {
        $command = new MakeControllerCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => []]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Controller name is required', $output);
    }

    public function testMakeControllerCreatesFile(): void
    {
        $command = new MakeControllerCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => ['User']]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertFileExists($this->testPath . '/app/Http/Controllers/UserController.php');
        $this->assertStringContainsString('Controller created', $output);
    }

    public function testMakeControllerAppendsControllerSuffix(): void
    {
        $command = new MakeControllerCommand($this->testPath);
        
        $command->run(['arguments' => ['User']]);
        
        $this->assertFileExists($this->testPath . '/app/Http/Controllers/UserController.php');
    }

    public function testMakeControllerFailsIfExists(): void
    {
        $dir = $this->testPath . '/app/Http/Controllers';
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/UserController.php', '<?php echo "exists";');
        
        $command = new MakeControllerCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => ['User']]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('already exists', $output);
    }

    public function testMakeModelCommandName(): void
    {
        $command = new MakeModelCommand('/tmp');
        
        $this->assertEquals('make:model', $command->getName());
    }

    public function testMakeModelRequiresName(): void
    {
        $command = new MakeModelCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => []]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Model name is required', $output);
    }

    public function testMakeModelCreatesFile(): void
    {
        $command = new MakeModelCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => ['User']]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertFileExists($this->testPath . '/app/Models/User.php');
        $this->assertStringContainsString('Model created', $output);
    }

    public function testMakeMigrationCommandName(): void
    {
        $command = new MakeMigrationCommand('/tmp');
        
        $this->assertEquals('make:migration', $command->getName());
    }

    public function testMakeMigrationRequiresName(): void
    {
        $command = new MakeMigrationCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => []]);
        $output = ob_get_clean();
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Migration name is required', $output);
    }

    public function testMakeMigrationCreatesFile(): void
    {
        $command = new MakeMigrationCommand($this->testPath);
        
        ob_start();
        $result = $command->run(['arguments' => ['CreateUsersTable']]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Migration created', $output);
        $this->assertCount(1, glob($this->testPath . '/database/migrations/*.php'));
    }

    public function testListRoutesCommand(): void
    {
        $command = new ListRoutesCommand();
        
        $this->assertEquals('route:list', $command->getName());
        
        ob_start();
        $result = $command->run(['arguments' => []]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Registered Routes', $output);
    }
}
