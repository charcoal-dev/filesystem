<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\InvalidPathException;

/**
 * Class PathInfo
 * @package Charcoal\Filesystem
 */
final readonly class PathInfo
{
    public string $path;
    public bool $validated;

    public PathType $type;
    public bool $readable;
    public bool $writable;
    public bool $executable;

    public string $basename;
    public string $separator;
    public PathContext $context;

    /**
     * @param SafePath|string $path
     * @throws InvalidPathException
     */
    public function __construct(SafePath|string $path)
    {
        if (is_string($path)) {
            $this->separator = DIRECTORY_SEPARATOR;
            $this->context = $this->separator === "\\" ? PathContext::Windows : PathContext::Unix;
            $absolute = $path;
        } else {
            $this->separator = $path->separator;
            $this->context = $path->context;
            $absolute = $path->absolute ? $path->path : null;
        }

        if (!$absolute) {
            throw new InvalidPathException($absolute ?? "", "Path is not absolute");
        }

        $this->path = $absolute;
        $this->basename = basename($this->path);
        $absolute = realpath($absolute);
        if (!$absolute) {
            $this->validated = $path instanceof SafePath;
            $this->type = PathType::Missing;
            $this->readable = false;
            $this->writable = false;
            $this->executable = false;
            return;
        }

        $this->validated = true;
        $this->type = match (true) {
            is_dir($this->path) => PathType::Directory,
            is_file($this->path) => PathType::File,
            is_link($this->path) => PathType::Link,
            default => PathType::Other
        };

        $this->readable = is_readable($this->path);
        $this->writable = is_writable($this->path);
        $this->executable = is_executable($this->path);
    }

    /**
     * @return self
     * @throws InvalidPathException
     */
    public function refresh(): self
    {
        if ($this->validated) {
            Filesystem::ClearPathStatCache($this->path);
        }

        return new self($this->path);
    }
}