<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * Class PathContext
 * @package Charcoal\Filesystem\Enums
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