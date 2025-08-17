<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exceptions;

use Charcoal\Filesystem\Node\AbstractNode;

/**
 * Class PermissionException
 * @package Charcoal\Filesystem\Exceptions
 */
class PermissionException extends NodeOpException
{
    public function __construct(AbstractNode $node, string $message, bool $captureLastError = false)
    {
        parent::__construct($node, $message, captureLastError: $captureLastError);
    }
}