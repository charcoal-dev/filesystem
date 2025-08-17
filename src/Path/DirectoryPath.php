<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Path;

use Charcoal\Filesystem\Enums\PathType;

/**
 * Class DirectoryPath
 * @package Charcoal\Filesystem\Path
 */
final readonly class DirectoryPath extends PathInfo
{
    public function __construct(SafePath|string $path)
    {
        parent::__construct($path);
        if ($this->type !== PathType::Directory) {
            throw new \InvalidArgumentException("Path must be a Directory, got: " . $this->type->name);
        }
    }
}