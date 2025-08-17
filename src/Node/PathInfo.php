<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Filesystem;
use Charcoal\Filesystem\SafePath;

/**
 * Class PathInfo
 * @package Charcoal\Filesystem
 */
final readonly class PathInfo
{
    public string $absolute;
    public string $separator;
    public PathContext $context;
    public string $basename;
    public bool $validated;
    public PathType $type;
    public bool $readable;
    public bool $writable;
    public bool $executable;

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

        $this->absolute = $absolute;
        $this->basename = basename($this->absolute);
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
            is_dir($this->absolute) => PathType::Directory,
            is_file($this->absolute) => PathType::File,
            is_link($this->absolute) => PathType::Link,
            default => PathType::Other
        };

        $this->readable = is_readable($this->absolute);
        $this->writable = is_writable($this->absolute);
        $this->executable = is_executable($this->absolute);
    }

    /**
     * @return void
     */
    public function clearStatCache(): void
    {
        if ($this->validated) {
            Filesystem::ClearPathStatCache($this->absolute);
        }
    }
}