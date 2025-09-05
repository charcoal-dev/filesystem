<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * This enumeration is used to identify the specific type of given
 * filesystem path. The types include files, directories, symbolic
 * links, missing paths, and others that do not fall into standard categories.
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
