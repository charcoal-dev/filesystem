<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * Class PathType
 * @package Charcoal\Filesystem
 */
enum PathType: int
{
    case FILE = 0x0a;
    case DIRECTORY = 0x14;
    case LINK = 0x1e;
}
