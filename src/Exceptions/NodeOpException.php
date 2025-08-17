<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Base\Support\ErrorHelper;
use Charcoal\Filesystem\Node\AbstractNode;

/**
 * Class NodeOpException
 * @package Charcoal\Filesystem\Exceptions
 */
class NodeOpException extends FilesystemException
{
    public function __construct(
        public readonly AbstractNode $node,
        string                       $message,
        bool                         $captureLastError = false,
        ?\Throwable                  $previous = null
    )
    {
        parent::__construct($message, previous: $captureLastError ?
            ErrorHelper::lastErrorToRuntimeException() : $previous);
    }
}
