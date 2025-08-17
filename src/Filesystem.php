<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Path\SafePath;

/**
 * Class Filesystem
 * @package Charcoal\Filesystem
 */
class Filesystem
{
    /**
     * @param string $path
     * @param PathContext $context
     * @return SafePath|null
     */
    public static function safePath(string $path, PathContext $context = PathContext::Unix): ?SafePath
    {
        try {
            return SafePath::for($path, $context);
        } catch (InvalidPathException) {
            return null;
        }
    }

    /**
     * @param string|null $path
     * @param bool $clear_realpath_cache
     * @return void
     */
    public static function ClearPathStatCache(?string $path = null, bool $clear_realpath_cache = false): void
    {
        clearstatcache($clear_realpath_cache, $path);
    }

    /**
     * @param int $utf
     * @param bool $littleEndian
     * @return string
     */
    public static function getBomBytes(int $utf, bool $littleEndian): string
    {
        return match ($utf) {
            8 => pack("C*", 0xEF, 0xBB, 0xBF),
            16 => $littleEndian ? pack("C*", 0xFF, 0xFE) : pack("C*", 0xFE, 0xFF),
            32 => $littleEndian ? pack("C*", 0xFF, 0xFE, 0x00, 0x00) : pack("C*", 0x00, 0x00, 0xFE, 0xFF),
            default => throw new \InvalidArgumentException('Argument for UTF must be 8, 16 or 32')
        };
    }

    /**
     * Pass few bytes (~4) from the beginning of a file to check if it contains UTF byte order mark
     * @param string $sof
     * @return int
     */
    public static function checkBom(string $sof): int
    {
        if (substr($sof, 0, 3) === static::getBomBytes(8, false)) {
            return 8;
        }

        $sof2 = substr($sof, 0, 2);
        if ($sof2 === static::getBomBytes(16, true) || $sof2 === static::getBomBytes(16, false)) {
            return 16;
        }

        $sof4 = substr($sof, 0, 4);
        if ($sof4 === static::getBomBytes(32, true) || $sof4 === static::getBomBytes(32, false)) {
            return 32;
        }

        return 0;
    }
}
