<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use Aphrodite\Engine\LogicCompiler;
use PHPUnit\Framework\TestCase;

class LogicCompilerTest extends TestCase
{
    private LogicCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new LogicCompiler();
    }

    public function testCompileIfThen(): void
    {
        $result = $this->compiler->compile('if user is active then return true');

        $this->assertIsString($result);
        $this->assertStringContainsString('if', $result);
    }

    public function testCompileWhenThen(): void
    {
        $result = $this->compiler->compile('when order is completed then update status');

        $this->assertIsString($result);
        $this->assertStringContainsString('if', $result);
    }

    public function testCompileMustBe(): void
    {
        $result = $this->compiler->compile('email must be unique');

        $this->assertIsString($result);
        $this->assertStringContainsString('assert', $result);
    }

    public function testCompileRuleArray(): void
    {
        $rule = [
            'condition' => '$status === "active"',
            'action' => 'return true',
        ];

        $result = $this->compiler->compileRule($rule);

        $this->assertIsString($result);
        $this->assertStringContainsString('if', $result);
    }

    public function testCompileRuleWithElse(): void
    {
        $rule = [
            'condition' => '$user is not null',
            'action' => 'return $user',
            'else' => 'return null',
        ];

        $result = $this->compiler->compileRule($rule);

        $this->assertIsString($result);
        $this->assertStringContainsString('else', $result);
    }

    public function testCompileConstraint(): void
    {
        $rule = [
            'field' => 'email',
            'constraint' => 'unique',
        ];

        $result = $this->compiler->compileRule($rule);

        $this->assertIsString($result);
        $this->assertStringContainsString('assert', $result);
    }

    public function testCompileConditionExpr(): void
    {
        $result = $this->compiler->compile('if value is greater than 10 then return success');

        $this->assertStringContainsString('>', $result);
    }

    public function testCompileLessThan(): void
    {
        $result = $this->compiler->compile('if count is less than 5 then return warning');

        $this->assertStringContainsString('<', $result);
    }

    public function testCompileIsNotNull(): void
    {
        $result = $this->compiler->compile('if user is not null then proceed');
        $this->assertIsString($result);
    }

    public function testCompileThrowAction(): void
    {
        $result = $this->compiler->compile('if error then throw error "Invalid input"');

        $this->assertStringContainsString('throw', $result);
    }

    public function testCompileSetAction(): void
    {
        $result = $this->compiler->compile('if active then set status to success');
        $this->assertIsString($result);
    }

    public function testCompileReturnAction(): void
    {
        $result = $this->compiler->compile('return success');

        $this->assertStringContainsString('return', $result);
    }
}
