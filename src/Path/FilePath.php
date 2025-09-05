<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Node\FileNode;

/**
 * A class representing a file path, extending functionality provided by PathInfo.
 * Ensures the provided path is of type File and provides access to associated file system node.
 */
final readonly class FilePath extends PathInfo
{
    /**
     * @param SafePath|string $path
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     */
    public function __construct(SafePath|string $path)
    {
        parent::__construct($path);
        if ($this->type !== PathType::File) {
            throw new PathTypeException($this, "Path must be a File, got: " . $this->type->name);
        }
    }

    /**
     * @return FileNode
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\PathNotFoundException
     */
    public function node(): FileNode
    {
        return new FileNode($this);
    }
}