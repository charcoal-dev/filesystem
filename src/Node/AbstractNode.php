<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Filesystem\Filesystem;

/**
 * Class AbstractNode
 * @package Charcoal\Filesystem\Node
 */
abstract class AbstractNode
{
    protected ?PathStats $stats = null;
    protected ?DirectoryNode $parent = null;

    /**
     * @param PathInfo $path
     */
    public function __construct(public readonly PathInfo $path)
    {
    }

    /**
     * @return int
     */
    abstract public function size(): int;

    /**
     * @return DirectoryNode|null
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     * @throws \Charcoal\Filesystem\Exceptions\PathNotFoundException
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     */
    public function parent(): ?DirectoryNode
    {
        if (!isset($this->parent)) {
            $this->parent = new DirectoryNode(new PathInfo(dirname($this->path->absolute)));
        }

        return $this->parent;
    }

    /**
     * @return PathStats
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     */
    public function stats(): PathStats
    {
        if (!isset($this->stats)) {
            return $this->stats = new PathStats($this->path);
        }

        return $this->stats;
    }

    /**
     * @return static
     */
    public function refresh(): static
    {
        if ($this->path->validated) {
            Filesystem::ClearPathStatCache($this->path->absolute);
        }

        return new static($this->path);
    }

    /**
     * @return void
     */
    protected function clearStats(): void
    {
        if ($this->path->validated) {
            Filesystem::ClearPathStatCache($this->path->absolute);
        }

        $this->stats = null;
    }
}