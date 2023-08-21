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

namespace Charcoal\Filesystem\Exception;

/**
 * Class FilesystemError
 * @package Charcoal\Filesystem\Exception
 */
enum FilesystemError: int
{
    case PATH_NOT_EXISTS = 0x64;
    case PATH_TYPE_ERR = 0xc8;
    case PATH_NOT_DIRECTORY = 0x12c;
    case PATH_NOT_FILE = 0x190;
    //case PATH_NOT_LINK = 0x1f4;
    case PATH_DELETED = 0x258;
    case CHMOD_FAIL = 0x2bc;
    case TIMESTAMP_FETCH_FAIL = 0x320;
    case UNSUPPORTED_PATH = 0x384;
    case PERMISSION_ERROR = 0x3e8;
    case IO_ERROR = 0x44c;
}

