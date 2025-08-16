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
enum PathType
{
    case Unchecked;
    case File;
    case Directory;
    case Link;
    case Missing;
    case Other;
}
