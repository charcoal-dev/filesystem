<?php
/*
 * This file is a part of "charcoal-dev/filesystem" package.
 * https://github.com/charcoal-dev/filesystem
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/filesystem/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Exception;

/**
 * Class FilesystemException
 * @package Charcoal\Filesystem\Exception
 */
class FilesystemException extends \Exception
{
    /**
     * @param \Charcoal\Filesystem\Exception\FilesystemError $error
     * @param string $message
     * @param array $data
     * @param \Throwable|null $previous
     */
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

