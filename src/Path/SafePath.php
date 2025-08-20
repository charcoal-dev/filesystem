<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Exceptions\InvalidPathException;

/**
 * Class SafePath
 * @package Charcoal\Filesystem\Path
 */
final readonly class SafePath
{
    protected function __construct(
        public string      $path,
        public bool        $absolute,
        public string      $separator,
        public PathContext $context
    )
    {
    }

    /**
     * @param string $path
     * @param PathContext|null $context
     * @return self
     * @throws InvalidPathException
     */
    public static function for(string $path, ?PathContext $context = PathContext::Unix): self
    {
        if(is_null($context)) {
            $context = DIRECTORY_SEPARATOR === "\\" ? PathContext::Windows : PathContext::Unix;
        }

        // Normalize path separators, trim whitespaces
        $path = trim(str_replace("\\", "/", $path));
        if (!$path) {
            throw new InvalidPathException($path, "Path cannot be empty");
        }

        // Check if a given path is absolute, matching given OS/Scheme
        $absolute = self::absoluteCheck($path, $context);
        if (PathContext::Scheme === $context && !$absolute) {
            throw new InvalidPathException($path, "Scheme path must be absolute");
        }

        if ($path === "/" && PathContext::Unix === $context && $absolute) {
            return new self($path, true, "/", $context);
        }

        // Replace multiple separators with a single separator
        $path = preg_replace('/\/+/', "/", trim($path, "/"));
        if (!$path || !self::pathRegExp($path, $context)) {
            throw new InvalidPathException($path, "Path is invalid");
        }

        // Replace path separators with OS/Scheme specific separator
        $separator = match ($context) {
            PathContext::Windows => "\\",
            default => "/",
        };

        if ($separator !== "/") {
            $path = str_replace("/", $separator, $path);
        }

        // Prepend an absolute path, OS/Scheme specific
        $path = match ($context) {
            PathContext::Unix => $absolute ? $separator . $path : $path,
            PathContext::Scheme => substr($path, 0, 6) .
                str_repeat($separator, $absolute ? 2 : 1) .
                substr($path, 6),
            default => $path
        };

        return new self($path, $absolute, $separator, $context);
    }

    /**
     * @param string $path
     * @param PathContext $context
     * @return bool
     */
    protected static function absoluteCheck(string $path, PathContext $context): bool
    {
        if ($context === PathContext::Unix) {
            return str_starts_with($path, "/");
        }

        if ($context === PathContext::Scheme) {
            if (!str_starts_with($path, "file://") || strlen($path) < 7) {
                return false;
            }

            $path = substr($path, 7);
            if (str_starts_with($path, "/")) {
                return true;
            }
        }

        return preg_match("#[A-Za-z]:/#", $path) === 1;
    }

    /**
     * @param string $normalized
     * @param PathContext $context
     * @return string|false
     */
    protected static function pathRegExp(string $normalized, PathContext $context): string|false
    {
        if ($normalized === "" || $normalized === "/") {
            return false;
        }

        // language=RegExp
        $regExp = "([\w.\-]*[\w\-]/?)";
        if ($context === PathContext::Unix) {
            $regExp = "(/)?" . $regExp;
        } else {
            $regExp = "([A-Za-z]:/)?" . $regExp;
            if ($context === PathContext::Scheme) {
                // Multiple separators were trimmed out
                $regExp = "(file:/)?" . $regExp;
            }
        }

        return preg_match("#^" . $regExp . "*$#", $normalized) === 1 ?
            $normalized : false;
    }

    /**
     * @return FilePath
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function isFile(): FilePath
    {
        return new FilePath($this);
    }

    /**
     * @return DirectoryPath
     * @throws InvalidPathException
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     */
    public function isDirectory(): DirectoryPath
    {
        return new DirectoryPath($this);
    }
}