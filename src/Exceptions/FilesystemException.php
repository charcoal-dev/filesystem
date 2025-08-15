<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Filesystem\Enums\FilesystemError;

/**
 * Class FilesystemException
 * @package Charcoal\Filesystem\Exception
 */
class FilesystemException extends \Exception
{
    public function __construct(
        public readonly FilesystemError $error,
        string                          $message = "",
        public readonly array           $data = [],
        ?\Throwable                     $previous = null
    )
    {
        parent::__construct($message, $this->error->value, $previous);
    }
}

