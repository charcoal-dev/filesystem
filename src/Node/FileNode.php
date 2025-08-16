<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\NodeOpException;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Exceptions\PermissionException;

/**
 * Class File
 * @package Charcoal\Filesystem
 */
class FileNode extends AbstractNode
{
    /**
     * @param PathInfo $path
     * @throws PathTypeException
     */
    public function __construct(PathInfo $path)
    {
        parent::__construct($path);
        if ($this->path->type !== PathType::File) {
            throw new PathTypeException($this->path,
                "Cannot instantiate path as FileNode object for " . $this->path->type->name);
        }
    }

    /**
     * @return int
     */
    public function size(): int
    {
        try {
            return $this->stats()->size;
        } catch (PathTypeException $e) {
            throw new \RuntimeException("Could not read file size", previous: $e);
        }
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return string
     * @throws NodeOpException
     * @throws PermissionException
     */
    public function read(int $offset = 0, ?int $length = null): string
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException("Offset must be positive for FileNode::read");
        }

        if (!$this->path->readable) {
            throw new PermissionException($this, "File is not readable");
        }

        $contents = @file_get_contents($this->path->absolute, false, null, $offset, $length);
        if ($contents === false) {
            throw new NodeOpException($this, "Read file op failed");
        }

        return $contents;
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return Buffer
     * @throws NodeOpException
     * @throws PermissionException
     */
    public function readToBuffer(int $offset = 0, ?int $length = null): Buffer
    {
        return new Buffer($this->read($offset, $length));
    }

    /**
     * @param string|AbstractByteArray $buffer
     * @param bool $append
     * @param bool $lock
     * @return $this
     * @throws NodeOpException
     * @throws PermissionException
     */
    public function write(string|AbstractByteArray $buffer, bool $append, bool $lock): static
    {
        if (!$this->path->writable) {
            throw new PermissionException($this, "File is not writable");
        }

        $flags = ($append ? FILE_APPEND : 0) | ($lock ? LOCK_EX : 0);
        $len = @file_put_contents($this->path->absolute,
            $buffer instanceof AbstractByteArray ? $buffer->raw() : $buffer,
            $flags);
        if (!is_int($len)) {
            throw new NodeOpException($this, "Write file op failed");
        }

        $this->clearStats();
        return $this;
    }
}
