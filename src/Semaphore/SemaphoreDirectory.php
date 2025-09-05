<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Semaphore;

use Charcoal\Contracts\Storage\Enums\StorageType;
use Charcoal\Contracts\Storage\StorageProviderInterface;
use Charcoal\Filesystem\Node\DirectoryNode;
use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Filesystem\Path\PathInfo;
use Charcoal\Semaphore\Contracts\SemaphoreProviderInterface;
use Charcoal\Semaphore\Exceptions\SemaphoreException;
use Charcoal\Semaphore\Exceptions\SemaphoreLockException;

/**
 * Represents a directory-based semaphore implementation.
 * This class allows the use of a directory as the foundation for file-based locking mechanisms.
 * It ensures proper permissions are in place for reliable semaphore functionality.
 */
readonly class SemaphoreDirectory implements SemaphoreProviderInterface, StorageProviderInterface
{
    public PathInfo $path;

    /**
     * @param DirectoryPath|DirectoryNode $directory
     * @throws SemaphoreException
     */
    public function __construct(DirectoryPath|DirectoryNode $directory)
    {
        $this->path = $directory instanceof DirectoryPath
            ? $directory
            : $directory->path;

        $permissions = DIRECTORY_SEPARATOR === "\\" ? $directory->writable
            : ($directory->writable && $directory->executable);
        if (!$permissions) {
            throw new SemaphoreException(
                sprintf('Semaphore lacks required directory perms for file locks: "%s/"',
                    basename($directory->absolute)));
        }
    }

    /**
     * @param string $lockId
     * @param float|null $concurrentCheckEvery
     * @param int $concurrentTimeout
     * @return FileLock
     * @throws SemaphoreLockException
     */
    public function obtainLock(
        string $lockId,
        ?float $concurrentCheckEvery = null,
        int    $concurrentTimeout = 0
    ): FileLock
    {
        return new FileLock($this, $lockId, $concurrentCheckEvery, $concurrentTimeout);
    }

    /**
     * @return StorageType
     */
    public function storageType(): StorageType
    {
        return StorageType::Filesystem;
    }

    /**
     * @return string
     */
    public function storageProviderId(): string
    {
        return $this->path->absolute;
    }
}
