<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Contracts\Storage\Enums\StorageType;
use Charcoal\Contracts\Storage\StorageProviderInterface;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Node\DirectoryNode;

/**
 * Represents a directory path in the filesystem. This class ensures that the provided path is of type Directory.
 * Extends functionality provided by PathInfo to specifically handle directory paths.
 */
final readonly class DirectoryPath extends PathInfo implements StorageProviderInterface
{
    /**
     * @param SafePath|string $path
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     */
    public function __construct(SafePath|string $path)
    {
        parent::__construct($path);
        if ($this->type !== PathType::Directory) {
            throw new PathTypeException($this, "Path must be a Directory, got: " . $this->type->name);
        }
    }

    /**
     * @return DirectoryNode
     * @throws \Charcoal\Filesystem\Exceptions\PathNotFoundException
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     */
    public function node(): DirectoryNode
    {
        return new DirectoryNode($this);
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
        return $this->absolute;
    }
}