<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Filesystem\Path\PathInfo;

/**
 * Class PathDeletedException
 * @package Charcoal\Filesystem\Exceptions
 */
class PathDeletedException extends \Exception
{
    public function __construct(public readonly PathInfo $deleted)
    {
        parent::__construct();
    }
}