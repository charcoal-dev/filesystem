<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Filesystem\Enums\PathType;

/**
 * Class FilePath
 * @package Charcoal\Filesystem\Path
 */
final readonly class FilePath extends PathInfo
{
    public function __construct(SafePath|string $path)
    {
        parent::__construct($path);
        if ($this->type !== PathType::File) {
            throw new \InvalidArgumentException("Path must be a File, got: " . $this->type->name);
        }
    }
}