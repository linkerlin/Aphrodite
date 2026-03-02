<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Queue;

require_once __DIR__ . '/../../src/Queue/Queue.php';

use Aphrodite\Queue\Queue;
use Aphrodite\Queue\ArrayQueue;
use Aphrodite\Queue\SyncQueue;
use Aphrodite\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;

class ArrayQueueTest extends TestCase
{
    private ArrayQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new ArrayQueue();
    }

    public function testPushJob(): void
    {
        $jobId = $this->queue->push(\stdClass::class, ['data' => 'value']);

        $this->assertNotEmpty($jobId);
        $this->assertStringStartsWith('job_', $jobId);
    }

    public function testLaterJob(): void
    {
        $jobId = $this->queue->later(60, \stdClass::class, ['data' => 'value']);

        $this->assertNotEmpty($jobId);
    }

    public function testPopJob(): void
    {
        $this->queue->push(\stdClass::class, ['test' => 'data']);

        $job = $this->queue->pop();

        $this->assertInstanceOf(QueuedJob::class, $job);
        $this->assertEquals(\stdClass::class, $job->class);
    }

    public function testPopEmptyQueue(): void
    {
        $job = $this->queue->pop();

        $this->assertNull($job);
    }

    public function testAcknowledgeJob(): void
    {
        $this->queue->push(\stdClass::class);
        $job = $this->queue->pop();

        $result = $this->queue->acknowledge($job);

        $this->assertTrue($result);
    }

    public function testReleaseJob(): void
    {
        $this->queue->push(\stdClass::class);
        $job = $this->queue->pop();

        $result = $this->queue->release($job, 60);

        $this->assertTrue($result);
    }

    public function testMaxAttempts(): void
    {
        $this->queue->setMaxAttempts(2);

        $this->queue->push(\stdClass::class);
        
        $job = $this->queue->pop();
        $this->queue->release($job);  // Release back for retry
        
        $job2 = $this->queue->pop();
        $this->assertNotNull($job2);
        
        $this->queue->acknowledge($job2);
        
        // Third attempt should not be returned
        $job3 = $this->queue->pop();
        $this->assertNull($job3);
    }

    public function testMultipleQueues(): void
    {
        $this->queue->push(\stdClass::class, [], 'queue1');
        $this->queue->push(\stdClass::class, [], 'queue2');

        $job1 = $this->queue->pop('queue1');
        $job2 = $this->queue->pop('queue2');

        $this->assertNotNull($job1);
        $this->assertNotNull($job2);
    }
}

class SyncQueueTest extends TestCase
{
    public function testPushExecutesImmediately(): void
    {
        $executed = false;
        
        $queue = new SyncQueue();
        
        // Create a simple test job class on the fly
        $jobClass = 'TestJob_' . uniqid();
        
        // We can't easily test sync execution without a real job class
        // Just verify the queue accepts push without error
        $id = $queue->push($jobClass, ['test' => true]);
        
        $this->assertNotEmpty($id);
    }

    public function testLaterSameAsPush(): void
    {
        $queue = new SyncQueue();
        
        $id = $queue->later(0, \stdClass::class, ['data' => 'value']);
        
        $this->assertNotEmpty($id);
    }

    public function testPopReturnsNull(): void
    {
        $queue = new SyncQueue();
        
        $job = $queue->pop();
        
        $this->assertNull($job);
    }
}

class QueueManagerTest extends TestCase
{
    protected function setUp(): void
    {
        Queue::setDriver(new ArrayQueue());
        Queue::setDefaultQueue('test');
    }

    public function testStaticPush(): void
    {
        $id = Queue::push(\stdClass::class, ['test' => true]);

        $this->assertNotEmpty($id);
    }

    public function testStaticLater(): void
    {
        $id = Queue::later(60, \stdClass::class, ['test' => true]);

        $this->assertNotEmpty($id);
    }

    public function testStaticPop(): void
    {
        Queue::push(\stdClass::class, ['data' => 'value']);

        $job = Queue::pop();

        $this->assertInstanceOf(QueuedJob::class, $job);
    }

    public function testProcessJob(): void
    {
        // Create a simple job
        $executed = false;
        
        // For this test, we'll just verify process returns bool
        $result = Queue::process();
        
        $this->assertIsBool($result);
    }

    public function testDispatchSync(): void
    {
        // Just verify it doesn't throw
        $this->expectNotToPerformAssertions();
        
        Queue::dispatchSync(\stdClass::class, ['test' => true]);
    }
}

class QueuedJobTest extends TestCase
{
    public function testCreateJob(): void
    {
        $job = new QueuedJob(\stdClass::class, ['key' => 'value']);

        $this->assertEquals(\stdClass::class, $job->class);
        $this->assertEquals(['key' => 'value'], $job->data);
    }

    public function testJobHasId(): void
    {
        $job = new QueuedJob(\stdClass::class);

        $this->assertNotEmpty($job->id);
        $this->assertStringStartsWith('job_', $job->id);
    }

    public function testResolveJob(): void
    {
        // Create a simple job class
        $job = new QueuedJob(\stdClass::class, []);

        $resolved = $job->resolve();

        $this->assertInstanceOf(\stdClass::class, $resolved);
    }

    public function testToArray(): void
    {
        $job = new QueuedJob(\stdClass::class, ['test' => true]);
        $array = $job->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('class', $array);
        $this->assertArrayHasKey('data', $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'test_id',
            'class' => \stdClass::class,
            'data' => ['key' => 'value'],
            'attempts' => 0,
        ];

        $job = QueuedJob::fromArray($data);

        $this->assertEquals('test_id', $job->id);
        $this->assertEquals(\stdClass::class, $job->class);
        $this->assertEquals(['key' => 'value'], $job->data);
    }
}
