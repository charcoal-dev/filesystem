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

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;

/**
 * Class File
 * @package Charcoal\Filesystem
 */
class File extends AbstractPath
{
    /**
     * @param string $path
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(string $path)
    {
        parent::__construct($path);
        if ($this->type !== PathType::FILE) {
            throw new FilesystemException(
                FilesystemError::PATH_NOT_FILE,
                "Cannot instantiate path as File object"
            );
        }
    }

    /**
     * Reads from file
     * @param int $offset
     * @param int|null $length
     * @return string
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function read(int $offset = 0, ?int $length = null): string
    {
        if (!$this->isReadable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "File is not readable");
        }

        $bytes = file_get_contents($this->pathIfExists(), false, null, $offset, $length);
        if ($bytes === false) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Read file op failed");
        }

        return $bytes;
    }

    /**
     * Reads file to instance of Buffer instead of string
     * @param int $offset
     * @param int|null $length
     * @return \Charcoal\Buffers\Buffer
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function readToBuffer(int $offset = 0, ?int $length = null): Buffer
    {
        return new Buffer($this->read($offset, $length));
    }

    /**
     * Writes to this file
     * @param string|\Charcoal\Buffers\AbstractByteArray $buffer
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function write(string|AbstractByteArray $buffer, bool $append, bool $lock): int
    {
        if (!$this->isWritable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "File is not writable");
        }

        $flags = ($append && $lock) ? (FILE_APPEND | LOCK_EX) :
            ($append ? FILE_APPEND : ($lock ? LOCK_EX : 0));
        $len = file_put_contents($this->pathIfExists(),
            $buffer instanceof AbstractByteArray ? $buffer->raw() : $buffer,
            $flags);
        if (!is_int($len)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Write file op failed");
        }

        return $len;
    }

    /**
     * No permission check is made
     * because to be able to delete a file, its parent's directory needs to be checked if writable
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function delete(): void
    {
        if (!@unlink($this->pathIfExists())) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to delete file");
        }

        $this->deleted = true;
    }

    /**
     * Returns file size in bytes
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function findSizeInBytes(): int
    {
        $size = filesize($this->pathIfExists());
        if (!is_int($size)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to check filesize");
        }

        return $size;
    }
}
