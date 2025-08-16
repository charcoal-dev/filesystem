<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\InvalidPathTypeException;

/**
 * Class PathStats
 * @package Charcoal\Filesystem\Node
 */
final readonly class PathStats
{
    public int $userId;
    public int $groupId;
    public int $changeTime;
    public int $accessTime;
    public int $modifyTime;
    public int $size;

    /**
     * @param PathInfo $node
     * @throws InvalidPathTypeException
     */
    public function __construct(PathInfo $node)
    {
        if (!in_array($node->type, [PathType::Directory, PathType::File, PathType::Link])) {
            throw new InvalidPathTypeException($node, "Path is not an existing file, directory or link");
        }

        [
            "uid" => $this->userId,
            "gid" => $this->groupId,
            "size" => $this->size,
            "atime" => $this->accessTime,
            "mtime" => $this->modifyTime,
            "ctime" => $this->changeTime,
        ] = stat($node->path);
    }
}