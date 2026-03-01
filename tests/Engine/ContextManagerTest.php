<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Aphrodite\Engine\ContextManager;

class ContextManagerTest extends TestCase
{
    private ContextManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ContextManager();
    }

    public function testAddAndGetEntity(): void
    {
        $this->manager->addEntity('User', ['fields' => ['email']]);
        $entity = $this->manager->getEntity('User');
        $this->assertIsArray($entity);
        $this->assertSame(['fields' => ['email']], $entity);
    }

    public function testGetNonExistentEntity(): void
    {
        $this->assertNull($this->manager->getEntity('NonExistent'));
    }

    public function testReset(): void
    {
        $this->manager->addEntity('User', []);
        $this->manager->reset();
        $this->assertNull($this->manager->getEntity('User'));
    }
}
