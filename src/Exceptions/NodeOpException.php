<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Filesystem\Node\AbstractNode;
use http\Exception\RuntimeException;

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
    )
    {
        if ($captureLastError) {
            if ($error = error_get_last()) {
                ["type" => $type, "message" => $errorStr, "file" => $file, "line" => $line] = $error;
                if ($type && $errorStr) {
                    $previous = new RuntimeException(
                        sprintf("[%d] %s in %s@%d", $type, $errorStr, $file, $line));
                }
            }
        }
        parent::__construct($message, previous: $previous ?? null);
    }
}
