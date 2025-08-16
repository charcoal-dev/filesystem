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
    protected ?PathStats $stats;

    public function __construct(public readonly PathInfo $path)
    {
    }

    abstract public function size(): int;

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
        if($this->path->validated) {
            Filesystem::ClearPathStatCache($this->path->absolute);
        }

        $this->stats = null;
    }
}