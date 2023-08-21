<?php
/*
 * This file is a part of "charcoal-dev/filesystem" package.
 * https://github.com/charcoal-dev/filesystem
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/filesystem/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Filesystem;

use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;

/**
 * Class Filesystem
 * @package Charcoal\Filesystem
 */
class Filesystem
{
    /**
     * @param string|null $path
     * @return void
     */
    public static function ClearPathStatCache(?string $path = null): void
    {
        clearstatcache(true, $path);
    }

    /**
     * @param string $mode
     * @param string $path
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public static function Chmod(string $mode, string $path): void
    {
        if (!preg_match('/^0[0-7]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid chmod argument, expecting octal number as string');
        }

        if (!chmod($path, intval($mode, 8))) {
            throw new FilesystemException(
                FilesystemError::CHMOD_FAIL,
                "Cannot change file/directory permissions"
            );
        }
    }

    /**
     * Generates UTF byte order mark bytes while minding the endianness
     * @param int $utf
     * @param bool $littleEndian
     * @return string
     */
    public static function BOM_Bytes(int $utf, bool $littleEndian): string
    {
        return match ($utf) {
            8 => pack("C*", 0xEF, 0xBB, 0xBF),
            16 => $littleEndian ? pack("C*", 0xFF, 0xFE) : pack("C*", 0xFE, 0xFF),
            32 => $littleEndian ? pack("C*", 0xFF, 0xFE, 0x00, 0x00) : pack("C*", 0x00, 0x00, 0xFE, 0xFF),
            default => throw new \InvalidArgumentException('Argument for UTF must be 8, 16 or 32')
        };
    }

    /**
     * Pass few bytes (~4) from beginning of a file to check if it contains UTF byte order mark
     * @param string $sof
     * @return int
     */
    public static function Check_BOM(string $sof): int
    {
        if (substr($sof, 0, 3) === static::BOM_Bytes(8, false)) {
            return 8;
        }

        $sof2 = substr($sof, 0, 2);
        if ($sof2 === static::BOM_Bytes(16, true) || $sof2 === static::BOM_Bytes(16, false)) {
            return 16;
        }

        $sof4 = substr($sof, 0, 4);
        if ($sof4 === static::BOM_Bytes(32, true) || $sof4 === static::BOM_Bytes(32, false)) {
            return 32;
        }

        return 0;
    }
}
