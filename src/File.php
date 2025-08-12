<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem;

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Enums\FilesystemError;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exception\FilesystemException;

/**
 * Class File
 * @package Charcoal\Filesystem
 */
class File extends AbstractPath
{
    /**
     * @throws FilesystemException
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
     * @throws FilesystemException
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
     * @throws FilesystemException
     */
    public function readToBuffer(int $offset = 0, ?int $length = null): Buffer
    {
        return new Buffer($this->read($offset, $length));
    }

    /**
     * @throws FilesystemException
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
     * @throws FilesystemException
     */
    public function delete(): void
    {
        if (!@unlink($this->pathIfExists())) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to delete file");
        }

        $this->deleted = true;
    }

    /**
     * @throws FilesystemException
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
