<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Filesystem\Path\PathInfo;

/**
 * Class PathAssertFailedException
 * @package Charcoal\Filesystem\Exceptions
 */
class PathAssertFailedException extends FilesystemException
{
    public function __construct(public readonly PathInfo $path, string $message)
    {
        parent::__construct($message);
    }
}