<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Enums;

/**
 * Represents a set of assertions that can be used to evaluate specific conditions on file system entities.
 */
enum Assert
{
    case Exists;
    case Readable;
    case Writable;
    case Executable;
    case IsFile;
    case IsDirectory;
}