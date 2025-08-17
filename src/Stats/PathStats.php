<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Stats;

use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Path\PathInfo;

/**
 * Class PathStats
 * @package Charcoal\Filesystem\Stats
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
     * @param PathInfo $path
     * @throws PathTypeException
     */
    public function __construct(PathInfo $path)
    {
        if (!in_array($path->type, [PathType::Directory, PathType::File, PathType::Link])) {
            throw new PathTypeException($path, "Path is not an existing file, directory or link");
        }

        [
            "uid" => $this->userId,
            "gid" => $this->groupId,
            "size" => $this->size,
            "atime" => $this->accessTime,
            "mtime" => $this->modifyTime,
            "ctime" => $this->changeTime,
        ] = stat($path->absolute);
    }
}