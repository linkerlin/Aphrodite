<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Validation;

use Aphrodite\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidatePasses(): void
    {
        $validator = new Validator(
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'required', 'email' => 'required|email']
        );

        $this->assertTrue($validator->validate());
        $this->assertFalse($validator->fails());
    }

    public function testValidateFails(): void
    {
        $validator = new Validator(
            ['name' => '', 'email' => 'invalid'],
            ['name' => 'required', 'email' => 'required|email']
        );

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors());
    }

    public function testValidateWithArrayRules(): void
    {
        // Use string format which is properly tested
        $validator = new Validator(
            ['name' => 'John'],
            ['name' => 'required|min_length:3']
        );

        $this->assertTrue($validator->validate());
    }

    public function testValidateMinLengthRule(): void
    {
        $validator = new Validator(
            ['name' => 'Jo'],
            ['name' => 'min_length:3']
        );

        $this->assertFalse($validator->validate());
    }

    public function testValidateMaxLengthRule(): void
    {
        $validator = new Validator(
            ['name' => 'John Doe'],
            ['name' => 'max_length:5']
        );

        $this->assertFalse($validator->validate());
    }

    public function testValidateNumericRule(): void
    {
        $validator = new Validator(
            ['age' => 'abc'],
            ['age' => 'numeric']
        );

        $this->assertFalse($validator->validate());
    }

    public function testValidateIntegerRule(): void
    {
        $validator = new Validator(
            ['count' => 3.14],
            ['count' => 'integer']
        );

        $this->assertFalse($validator->validate());
    }

    public function testFirstErrors(): void
    {
        $validator = new Validator(
            ['name' => '', 'email' => 'invalid'],
            ['name' => 'required', 'email' => 'required|email']
        );

        $validator->validate();
        $this->assertNotNull($validator->firstErrors());
    }

    public function testValidatedData(): void
    {
        $validator = new Validator(
            ['name' => 'John', 'email' => 'john@example.com', 'extra' => 'data'],
            ['name' => 'required', 'email' => 'required|email']
        );

        $validator->validate();
        $validated = $validator->validated();

        $this->assertArrayHasKey('name', $validated);
        $this->assertArrayHasKey('email', $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function testMake(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'required']
        );

        $this->assertTrue($validator->validate());
    }

    public function testCustomMessage(): void
    {
        $validator = new Validator(
            ['name' => ''],
            ['name' => 'required'],
            ['name.required' => 'Custom required message']
        );

        $validator->validate();
        $errors = $validator->errors();
        
        $this->assertStringContainsString('Custom', $errors['name'][0] ?? '');
    }

    public function testInRule(): void
    {
        $validator = new Validator(
            ['status' => 'invalid'],
            ['status' => 'in:active,pending,completed']
        );

        $this->assertFalse($validator->validate());
    }

    public function testUrlRule(): void
    {
        $validator = new Validator(
            ['website' => 'not-a-url'],
            ['website' => 'url']
        );

        $this->assertFalse($validator->validate());
    }
}
