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
}
