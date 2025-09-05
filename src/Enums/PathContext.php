<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * Represents the context of a file path, allowing differentiation
 * between Unix, Windows, and Scheme-based paths.
 */
enum PathContext
{
    case Unix;
    case Windows;
    case Scheme;

    /**
     * @return self
     */
    public static function fromOS(): self
    {
        return DIRECTORY_SEPARATOR === "\\" ? self::Windows : self::Unix;
    }
}