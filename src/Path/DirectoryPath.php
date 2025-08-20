<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Node\DirectoryNode;

/**
 * Class DirectoryPath
 * @package Charcoal\Filesystem\Path
 */
final readonly class DirectoryPath extends PathInfo
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

}