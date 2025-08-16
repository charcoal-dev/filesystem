<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Filesystem\PathInfo;

/**
 * Class PathStat
 * @package Charcoal\Filesystem\Node
 */
final readonly class PathStat
{
    public int $userId;
    public int $groupId;
    public int $changeTime;
    public int $accessTime;
    public int $modifyTime;
    public int $size;

    /**
     * @param PathInfo $node
     */
    public function __construct(PathInfo $node)
    {
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