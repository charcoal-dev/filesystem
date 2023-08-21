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

namespace Charcoal\Filesystem;

use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;

/**
 * Class AbstractPath
 * @package Charcoal\Filesystem
 */
abstract class AbstractPath
{
    public readonly string $path;
    public readonly string $basename;
    public readonly PathType $type;

    private ?int $size = null;
    private ?bool $isReadable = null;
    private ?bool $isWritable = null;
    private ?bool $isExecutable = null;
    private ?int $tsModified = null;
    private ?int $tsAccess = null;
    private ?int $tsCtime = null;

    protected bool $deleted = false;

    /**
     * @param string $path
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(string $path)
    {
        $realpath = realpath($path);
        if (!$realpath) {
            throw new FilesystemException(
                FilesystemError::PATH_NOT_EXISTS,
                "Path to file/directory could not be resolved"
            );
        }

        $this->path = $realpath;
        $this->basename = basename($this->path);
        $this->type = match (true) {
            is_dir($this->path) => PathType::DIRECTORY,
            is_file($this->path) => PathType::FILE,
            is_link($this->path) => PathType::LINK,
            default => throw new FilesystemException(FilesystemError::PATH_TYPE_ERR)
        };
    }

    /**
     * @return $this
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function reload(): static
    {
        $realpath = realpath($this->path);
        if (!$realpath) {
            throw new FilesystemException(
                FilesystemError::PATH_DELETED,
                "Path to file/directory no longer exists"
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "path" => $this->path,
            "basename" => $this->basename,
            "type" => $this->type->name,
        ];
    }

    /**
     * @return $this
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function clearStatCache(): static
    {
        Filesystem::ClearPathStatCache($this->pathIfExists());
        $this->isReadable = $this->isWritable = $this->isExecutable = null;
        $this->tsAccess = $this->tsModified = $this->tsCtime = null;
        $this->size = null; // Clear stored size
        return $this;
    }

    /**
     * @return \Charcoal\Filesystem\Directory
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function parent(): Directory
    {
        return new Directory(dirname($this->path));
    }

    /**
     * @return int
     */
    final public function size(): int
    {
        if (!is_int($this->size)) {
            $this->size = $this->findSizeInBytes();
        }

        return $this->size;
    }

    /**
     * @return int
     */
    abstract protected function findSizeInBytes(): int;

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $dirSuffix
     * @return string
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function pathIfExists(bool $dirSuffix = false): string
    {
        if ($this->deleted) {
            throw new FilesystemException(
                FilesystemError::PATH_DELETED,
                "Path to file/directory no longer exists"
            );
        }

        if (!$dirSuffix || $this->type !== PathType::DIRECTORY) {
            return $this->path;
        }

        return $this->path . DIRECTORY_SEPARATOR . ".";
    }

    /**
     * @param string $mode
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function chmod(string $mode): void
    {
        Filesystem::Chmod($mode, $this->pathIfExists());
        Filesystem::ClearPathStatCache($this->path);
    }

    /**
     * @return bool
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function isReadable(): bool
    {
        return $this->isReadable ??= is_readable($this->pathIfExists());
    }

    /**
     * @return bool
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function isWritable(): bool
    {
        return $this->isWritable ??= is_writable($this->pathIfExists());
    }

    /**
     * @return bool
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function isExecutable(): bool
    {
        return $this->isExecutable ??= is_executable($this->pathIfExists());
    }

    /**
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function modifiedOn(): int
    {
        if (!$this->tsModified) {
            $this->tsModified = filemtime($this->pathIfExists(true)) ?:
                throw new FilesystemException(FilesystemError::TIMESTAMP_FETCH_FAIL, data: ["modified"]);
        }

        return $this->tsModified;
    }

    /**
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function lastAccessOn(): int
    {
        if (!$this->tsAccess) {
            $this->tsAccess = fileatime($this->pathIfExists(true)) ?:
                throw new FilesystemException(FilesystemError::TIMESTAMP_FETCH_FAIL, data: ["access"]);
        }

        return $this->tsAccess;
    }

    /**
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function ctime(): int
    {
        if (!$this->tsCtime) {
            $this->tsCtime = filectime($this->pathIfExists(true)) ?:
                throw new FilesystemException(FilesystemError::TIMESTAMP_FETCH_FAIL, data: ["ctime"]);
        }

        return $this->tsCtime;
    }
}