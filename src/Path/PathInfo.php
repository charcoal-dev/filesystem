<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Base\Support\Helpers\ErrorHelper;
use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Filesystem;

/**
 * Class PathInfo
 * @package Charcoal\Filesystem\Path
 */
readonly class PathInfo
{
    public PathContext $context;
    public string $absolute;
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
            $this->context = DIRECTORY_SEPARATOR === "\\" ? PathContext::Windows : PathContext::Unix;
            $absolute = $path;
        } else {
            $this->context = $path->context;
            $absolute = $path->absolute ? $path->path : null;
        }

        if (!$absolute) {
            throw new InvalidPathException($absolute ?? "", "Path is not absolute");
        }

        $this->absolute = $absolute;
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
     * @param string ...$path
     * @return SafePath
     * @throws InvalidPathException
     */
    public function join(string ...$path): SafePath
    {
        return SafePath::for($this->absolute . DIRECTORY_SEPARATOR .
            trim(implode(DIRECTORY_SEPARATOR, array_map("trim", $path)), "\\./"),
            context: null);
    }

    /**
     * @param bool $clear_realpath_cache
     * @return void
     */
    public function clearStatCache(bool $clear_realpath_cache = false): void
    {
        if ($this->validated) {
            Filesystem::ClearPathStatCache($this->absolute, $clear_realpath_cache);
        }
    }
}