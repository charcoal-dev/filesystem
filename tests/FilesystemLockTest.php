<?php
/**
 * Part of the "charcoal-dev/semaphore" package.
 * @link https://github.com/charcoal-dev/semaphore
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Filesystem\Semaphore\SemaphoreDirectory;
use Charcoal\Semaphore\Contracts\SemaphoreProviderInterface;
use Charcoal\Semaphore\Enums\SemaphoreLockError;
use PHPUnit\Framework\TestCase;

/**
 * Class FilesystemLockTest
 * @package Charcoal\Tests\Semaphore\Filesystem
 */
class FilesystemLockTest extends TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreLockException
     */
    public function testBasicLock(): void
    {
        $resourceId = "basic_resource_1";
        $semaphore = new SemaphoreDirectory(new DirectoryPath(__DIR__ . DIRECTORY_SEPARATOR . "tmp"));

        $lock1 = $semaphore->obtainLock($resourceId, concurrentCheckEvery: null);
        $this->assertTrue($lock1->isLocked(), "Lock obtained successfully");

        // Send concurrent request:
        $this->expectExceptionCode(SemaphoreLockError::CONCURRENT_REQUEST_BLOCKED->value);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $lock2 = $semaphore->obtainLock($resourceId);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreLockException
     */
    public function testConcurrencyTimeout(): void
    {
        $resourceId = "some_resource_2";
        $semaphore = new SemaphoreDirectory(new DirectoryPath(__DIR__ . DIRECTORY_SEPARATOR . "tmp"));

        $lock1 = $semaphore->obtainLock($resourceId, concurrentCheckEvery: null);
        $this->assertTrue($lock1->isLocked(), "Lock obtained successfully");

        // This will hang for up to 3 seconds...
        // Check every 0.5 second, maximum of 3 seconds
        $this->expectExceptionCode(SemaphoreLockError::CONCURRENT_REQUEST_TIMEOUT->value);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $lock2 = $semaphore->obtainLock($resourceId, concurrentCheckEvery: 0.5, concurrentTimeout: 3);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreLockException
     * @throws \Throwable
     */
    public function testCheckLockReleasedDuringConcurrency(): void
    {
        $resourceId = "some_resource_3";
        $semaphore = new SemaphoreDirectory(new DirectoryPath(__DIR__ . DIRECTORY_SEPARATOR . "tmp"));

        $fiber = new \Fiber(function (SemaphoreProviderInterface $semaphore, string $resourceId) {
            $lock1 = $semaphore->obtainLock($resourceId, concurrentCheckEvery: null);
            $this->assertTrue($lock1->isLocked(), "Lock obtained successfully");

            sleep(3);
            $lock1->releaseLock();

            return $lock1;
        });

        $fiber->start($semaphore, $resourceId);

        // This will hang for up to 3 seconds... (as soon as fiber releases lock)
        // Check every 0.25 second.
        $lock2 = $semaphore->obtainLock($resourceId, concurrentCheckEvery: 0.25);
        $this->assertTrue($lock2->isLocked());

        $lock1 = $fiber->getReturn();
        $this->assertFalse($lock1->isLocked());
    }
}
