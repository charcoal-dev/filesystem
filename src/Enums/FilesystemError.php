<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * Class FilesystemError
 * @package Charcoal\Filesystem\Enums
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

