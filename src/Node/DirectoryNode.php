<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Node;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\FilesystemException;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Exceptions\NodeOpException;
use Charcoal\Filesystem\Exceptions\PathDeletedException;
use Charcoal\Filesystem\Exceptions\PathNotFoundException;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Exceptions\PermissionException;
use Charcoal\Filesystem\Filesystem;
use Charcoal\Filesystem\SafePath;

/**
 * Class DirectoryNode
 * @package Charcoal\Filesystem\Node
 */
class DirectoryNode extends AbstractNode
{
    /**
     * @throws PathNotFoundException
     * @throws PathTypeException
     */
    public function __construct(PathInfo $path)
    {
        parent::__construct($path);
        if ($this->path->type === PathType::Missing) {
            throw new PathNotFoundException($this->path, "No such directory exists at given path");
        }

        if ($this->path->type !== PathType::Directory) {
            throw new PathTypeException($this->path,
                "Cannot instantiate path as " . ObjectHelper::baseClassName(static::class) .
                " object for " . $this->path->type->name);
        }
    }

    /**
     * @throws InvalidPathException
     */
    public function childPathInfo(string $path, bool $pathIsTrusted = false): PathInfo
    {
        if (!$path || $path === ".") {
            throw new InvalidPathException($path, "Relative path to child cannot be empty");
        }

        if (!$pathIsTrusted) {
            $safePath = SafePath::for($path, $this->path->context);
            if ($safePath->absolute) {
                throw new InvalidPathException($safePath->path,
                    "Cannot create directories; Target path not relative to this directory");
            }
        }

        return new PathInfo($this->path->absolute . $this->path->separator . $path);
    }

    /**
     * @throws InvalidPathException
     * @throws PathNotFoundException
     * @throws PathTypeException
     */
    public function child(string $path, bool $pathIsTrusted): DirectoryNode|FileNode
    {
        $path = $this->childPathInfo($path, $pathIsTrusted);
        return match ($path->type) {
            PathType::File => new FileNode($path),
            PathType::Directory => new DirectoryNode($path),
            PathType::Missing => throw new PathNotFoundException($path, "Path does not exist"),
            default => throw new PathTypeException($path, "Unsupported path type"),
        };
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function file(string $path, bool $pathIsTrusted, bool $createIfNotExists = false): FileNode
    {
        $childPath = $this->childPathInfo($path, $pathIsTrusted);
        if ($createIfNotExists && $childPath->type === PathType::Missing) {
            return $this->touch($path, true);
        }

        return new FileNode($childPath);
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function read(
        string  $path,
        bool    $pathIsTrusted,
        int     $offset = 0,
        ?int    $length = null,
        ?Buffer $buffer = null
    ): string
    {
        return (new FileNode($this->childPathInfo($path, $pathIsTrusted)))
            ->read($offset, $length, $buffer);
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function directory(string $path, bool $pathIsTrusted, bool $createIfNotExists = false): DirectoryNode
    {
        $childPath = $this->childPathInfo($path, $pathIsTrusted);
        if ($createIfNotExists && $childPath->type === PathType::Missing) {
            return $this->mkDir($path);
        }

        return new DirectoryNode($childPath);
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function touch(string $path, bool $pathIsTrusted): FileNode
    {
        if (!$this->path->readable) {
            throw new PermissionException($this, "Directory is not readable");
        }

        $childPath = $this->childPathInfo($path, $pathIsTrusted);
        if ($childPath->type !== PathType::Missing) {
            throw new NodeOpException($this, "Cannot touch; Target path exists as " . $childPath->type->name);
        }

        error_clear_last();
        if (!@touch($childPath->absolute)) {
            throw new NodeOpException($this, "Failed to create new file", captureLastError: true);
        }

        Filesystem::ClearPathStatCache($childPath->absolute);
        Filesystem::ClearPathStatCache($childPath->parent);
        return new FileNode(new PathInfo($childPath->absolute));
    }

    /**
     * @return array<string>
     * @throws NodeOpException
     * @throws PermissionException
     */
    public function scanDir(bool $returnAbsolutePaths = false, int $sort = SCANDIR_SORT_NONE): array
    {
        if (!$this->path->readable) {
            throw new PermissionException($this, "Directory is not readable");
        }

        $directoryPath = $this->path->absolute . $this->path->separator;
        $final = [];
        error_clear_last();
        $scan = @scandir($directoryPath, $sort);
        if (!is_array($scan)) {
            throw new NodeOpException($this, "Failed to run scan on directory", captureLastError: true);
        }

        foreach ($scan as $file) {
            if (in_array($file, [".", ".."])) {
                continue; // Skip dots
            }

            $final[] = $returnAbsolutePaths ? $directoryPath . DIRECTORY_SEPARATOR . $file : $file;
        }

        return $final;
    }

    /**
     * @return array<string>
     * @throws NodeOpException
     * @throws PermissionException
     */
    public function glob(string $pattern, bool $returnAbsolutePaths = false, int $flags = 0): array
    {
        if (!$this->path->readable) {
            throw new PermissionException($this, "Directory is not readable");
        }

        if (!preg_match('/^[\w*\-.]+$/', $pattern)) {
            throw new \InvalidArgumentException('Unacceptable glob pattern');
        }

        $directoryPath = $this->path->absolute . $this->path->separator;
        $final = [];
        error_clear_last();
        $glob = @glob($directoryPath . $pattern, $flags);
        if (!is_array($glob)) {
            throw new NodeOpException($this, "Failed to run glob on directory", captureLastError: true);
        }

        foreach ($glob as $file) {
            if (in_array($file, [".", ".."])) {
                continue; // Skip dots
            }

            $final[] = $returnAbsolutePaths ? $directoryPath . $file : $file;
        }

        return $final;
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function mkDir(string $path, bool $pathIsTrusted = false, string $mode = "0777"): static
    {
        if (!preg_match('/^0[0-7]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid mode argument, expecting octal number as string');
        }

        if (!$this->path->writable) {
            throw new PermissionException($this, "Directory is not writable");
        }

        // Verify that the target path does not already exist, and it in fact relative to this directory
        $directories = $this->childPathInfo($path, $pathIsTrusted);
        if ($directories->type !== PathType::Missing) {
            throw new NodeOpException($this, "Cannot create directories; Target path exists as " .
                $directories->type->name);
        }

        // Create the target directory(ies) using absolute path
        error_clear_last();
        if (!@mkdir($directories->absolute, intval($mode, 8), true)) {
            throw new NodeOpException($this, "Failed to create child directories", captureLastError: true);
        }

        Filesystem::ClearPathStatCache($directories->absolute);
        return new static(new PathInfo($directories->absolute));
    }

    /**
     * @throws InvalidPathException
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function deleteChild(string $path, bool $pathIsTrusted = false): void
    {
        if (!$this->path->writable) {
            throw new PermissionException($this, "Directory is not writable");
        }

        $childPath = $this->childPathInfo($path, $pathIsTrusted);
        if ($childPath->type === PathType::Missing) {
            throw new PathNotFoundException($childPath,
                "Cannot delete child; Target path does not exist");
        }

        if ($childPath->type === PathType::Directory) {
            try {
                (new static($childPath))->deleteSelf();
            } catch (PathDeletedException) {
            }
        }

        error_clear_last();
        if (!@unlink($childPath->absolute)) {
            throw new NodeOpException($this, "Failed to delete child file", captureLastError: true);
        }
    }

    /**
     * @return never
     * @throws NodeOpException
     * @throws PathDeletedException <= On Success
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function deleteSelf(): never
    {
        if (!$this->path->writable) {
            throw new PermissionException($this, "Directory is not writable");
        }

        $this->flush(false);
        error_clear_last();
        if (!@rmdir($this->path->absolute . $this->path->separator)) {
            throw new NodeOpException($this, "Failed to delete directory", captureLastError: true);
        }

        throw new PathDeletedException($this->path);
    }

    /**
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws PermissionException
     */
    public function flush(bool $ignoreFails = false): int
    {
        if (!$this->path->writable) {
            throw new PermissionException($this, "Directory is not writable");
        }

        $deleted = 0;
        foreach ($this->scanDir(true) as $path) {
            if (is_dir($path)) {
                try {
                    $deleted += (new DirectoryNode(new PathInfo($path)))->flush($ignoreFails);
                } catch (InvalidPathException $e) {
                    // Path is always valid here, using \RuntimeException for consistency
                    throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                error_clear_last();
                if (!@rmdir($path)) {
                    if (!$ignoreFails) {
                        throw new NodeOpException($this,
                            sprintf('Could not delete sub-directory "%s"', basename($path)),
                            captureLastError: true);
                    }
                }

                continue;
            }

            error_clear_last();
            if (!@unlink($path)) {
                if ($ignoreFails) {
                    continue;
                }

                throw new NodeOpException($this, sprintf('Could not delete file "%s"', basename($path)),
                    captureLastError: true);
            }

            $deleted++;
        }

        return $deleted;
    }

    /**
     * @return int
     * @throws NodeOpException
     */
    public function size(): int
    {
        $bytes = 0;
        $scanned = $this->scanDir(true);
        foreach ($scanned as $file) {
            if (is_dir($file)) {
                try {
                    $bytes += (new static(new PathInfo($file)))->size();
                } catch (FilesystemException $e) {
                    throw new \RuntimeException($e->getMessage());
                }

                continue;
            }

            error_clear_last();
            $fileSize = @filesize($file);
            if (!is_int($fileSize)) {
                throw new NodeOpException($this,
                    sprintf('Could not get file size for file "%s"', basename($file)),
                    captureLastError: true);
            }

            $bytes += $fileSize;
        }

        return $bytes;
    }
}
