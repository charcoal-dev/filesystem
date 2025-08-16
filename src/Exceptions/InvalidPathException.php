<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

/**
 * Class InvalidPathException
 * @package Charcoal\Filesystem\Exceptions
 */
class InvalidPathException extends FilesystemException
{
    public function __construct(public readonly string $path, string $message)
    {
        parent::__construct($message);
    }
}