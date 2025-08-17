<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Base\Support\ErrorHelper;
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
    public string $parent;

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
        $this->parent = dirname($this->absolute);
        $absolute = realpath($absolute);
        if (!$absolute) {
            $this->validated = $path instanceof SafePath;
            $this->type = PathType::Missing;
            $this->readable = false;
            $this->writable = false;
            $this->executable = false;
            return;
        }

        error_clear_last();
        $this->validated = true;
        $this->type = match (true) {
            @is_file($this->absolute) => PathType::File,
            @is_dir($this->absolute) => PathType::Directory,
            default => PathType::Other
        };

        $permissions = $this->type === PathType::File ||
            $this->type === PathType::Directory;

        $this->readable = $permissions && @is_readable($this->absolute);
        $this->writable = $permissions && @is_writable($this->absolute);
        $this->executable = $permissions && @is_executable($this->absolute);

        $error = ErrorHelper::lastErrorToRuntimeException();
        if ($error) {
            throw new InvalidPathException($this->absolute,
                "Cannot instantiate PathInfo; Caught a filesystem error",
                previous: $error);
        }
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