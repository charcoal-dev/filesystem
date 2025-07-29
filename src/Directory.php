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
 * Class Directory
 * @package Charcoal\Filesystem
 */
class Directory extends AbstractPath
{
    /**
     * @param string $path
     * @param bool $childPathValidations
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(
        string      $path,
        public bool $childPathValidations = false
    )
    {
        parent::__construct($path);
        if ($this->type !== PathType::DIRECTORY) {
            throw new FilesystemException(
                FilesystemError::PATH_NOT_DIRECTORY,
                "Cannot instantiate path as Directory object"
            );
        }
    }

    /**
     * Minor overhead from RegExp calls can be avoided by setting $validations to FALSE, but
     * Always use $validations=TRUE whenever path is being supplied by dynamic variable/untrusted user input
     * @param string $path
     * @param bool $validations
     * @return string
     */
    public function pathToChild(string $path, ?bool $validations = null): string
    {
        $validations = $validations ?? $this->childPathValidations;
        if ($validations) {
            $sep = '([/\\\])';
            $path = preg_replace('#' . $sep . '{2,}#', DIRECTORY_SEPARATOR, $path);
            if (!preg_match('#^(' . $sep . '?[\w\-.]+' . $sep . '?)+$#', $path)) {
                throw new \InvalidArgumentException('Invalid suffix path');
            } elseif (preg_match('#(\.+' . $sep . ')#', $path)) {
                throw new \InvalidArgumentException('Path contains illegal references');
            }
        }

        return $this->path . DIRECTORY_SEPARATOR . ltrim($path, '\/\\');
    }

    /**
     * Checks if child exists in directory, returns PathType enum
     * @param string $pathToChild
     * @return \Charcoal\Filesystem\PathType|null
     */
    public function contains(string $pathToChild): ?PathType
    {
        $child = $this->pathToChild($pathToChild);
        return match (true) {
            is_dir($child) => PathType::DIRECTORY,
            is_file($child) => PathType::FILE,
            is_link($child) => PathType::LINK,
            default => null,
        };
    }

    /**
     * Gets an instance of Directory or File based on given path
     * @param string $pathToChild
     * @return \Charcoal\Filesystem\Directory|\Charcoal\Filesystem\File
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function getChild(string $pathToChild): Directory|File
    {
        return match ($this->contains($pathToChild)) {
            PathType::DIRECTORY => new Directory($this->pathToChild($pathToChild)),
            PathType::FILE => new File($this->pathToChild($pathToChild)),
            null => throw new FilesystemException(FilesystemError::PATH_NOT_EXISTS,
                "No such file/directory exists in this directory"),
            default => throw new FilesystemException(FilesystemError::UNSUPPORTED_PATH,
                "No class provided by Filesystem lib")
        };
    }

    /**
     * Gets an Instance of File on child directory
     * @param string $pathToChild
     * @param bool $createIfNotExists
     * @return \Charcoal\Filesystem\File
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function getFile(string $pathToChild, bool $createIfNotExists = false): File
    {
        try {
            return new File($this->pathToChild($pathToChild));
        } catch (FilesystemException $e) {
            if ($createIfNotExists && $e->error === FilesystemError::PATH_NOT_EXISTS) {
                $this->writeToFile($pathToChild, ""); // Create new blank file
                Filesystem::ClearPathStatCache($this->pathToChild($pathToChild));
                return $this->getFile($pathToChild, false);
            }

            throw $e;
        }
    }

    /**
     * Gets an instance of Directory on a child directory
     * @param string $pathToChild
     * @param bool $createIfNotExists
     * @return \Charcoal\Filesystem\Directory
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function getDirectory(string $pathToChild, bool $createIfNotExists = false): Directory
    {
        try {
            return new Directory($this->pathToChild($pathToChild));
        } catch (FilesystemException $e) {
            if ($createIfNotExists && $e->error === FilesystemError::PATH_NOT_EXISTS) {
                return $this->createDirectories($pathToChild);
            }

            throw $e;
        }
    }

    /**
     * Reads a file inside directory
     * @param string $fileName
     * @param int $offset
     * @param int|null $length
     * @return string
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function readFile(string $fileName, int $offset = 0, ?int $length = null): string
    {
        $bytes = @file_get_contents($this->pathToChild($fileName), false, null, $offset, $length);
        if ($bytes === false) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Read file op failed");
        }

        return $bytes;
    }

    /**
     * Same as readFile method but returns instance Buffer instead of string
     * @param string $fileName
     * @param int $offset
     * @param int|null $length
     * @return \Charcoal\Buffers\Buffer
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function readFileToBuffer(string $fileName, int $offset = 0, ?int $length = null): Buffer
    {
        return new Buffer($this->readFile($fileName, $offset, $length));
    }

    /**
     * Writes to a file in directory, if it doesn't exist, creates one
     * @param string $fileName
     * @param string|\Charcoal\Buffers\AbstractByteArray $buffer
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function writeToFile(
        string                   $fileName,
        string|AbstractByteArray $buffer,
        bool                     $append = false,
        bool                     $lock = false,
    ): int
    {
        $flags = ($append && $lock) ? (FILE_APPEND | LOCK_EX) :
            ($append ? FILE_APPEND : ($lock ? LOCK_EX : 0));
        $len = @file_put_contents($this->pathToChild($fileName),
            $buffer instanceof AbstractByteArray ? $buffer->raw() : $buffer,
            $flags);
        if (!is_int($len)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to write file");
        }

        return $len;
    }

    /**
     * Performs a "scan" in directory
     * @param bool $returnAbsolutePaths
     * @param int $sort
     * @return array
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function scan(bool $returnAbsolutePaths = false, int $sort = SCANDIR_SORT_NONE): array
    {
        if (!$this->isReadable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "Directory is not readable");
        }

        $directoryPath = $this->pathIfExists();
        $final = [];
        $scan = scandir($directoryPath, $sort);
        if (!is_array($scan)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to scan directory");
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
     * Performs "glob" in directory
     * @param string $pattern
     * @param bool $returnAbsolutePaths
     * @param int $flags
     * @return array
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function glob(string $pattern, bool $returnAbsolutePaths = false, int $flags = 0): array
    {
        if (!$this->isReadable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "Directory is not readable");
        }

        if (!preg_match('/^[\w*\-.]+$/', $pattern)) {
            throw new \InvalidArgumentException('Unacceptable glob pattern');
        }

        $directoryPath = $this->pathIfExists() . DIRECTORY_SEPARATOR;
        $final = [];
        $glob = glob($directoryPath . $pattern, $flags);
        if (!is_array($glob)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to run glob on directory");
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
     * Creates a directory or multiple directories depending on given path argument
     * @param string $path
     * @param string $mode
     * @return \Charcoal\Filesystem\Directory
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function createDirectories(string $path, string $mode = "0777"): Directory
    {
        if (!$this->isWritable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "Directory is not writable");
        }

        $newDirectoryPath = $this->pathToChild($path);
        if (!preg_match('/^0[0-7]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid mode argument, expecting octal number as string');
        }

        if (!mkdir($newDirectoryPath, intval($mode, 8), true)) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to create child directories");
        }

        Filesystem::ClearPathStatCache($newDirectoryPath);
        return new Directory($newDirectoryPath);
    }

    /**
     * changes CHMOD of a child file or directory
     * @param string $pathToChild
     * @param string $mode
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function childChmod(string $pathToChild, string $mode = "0755"): void
    {
        if (!$pathToChild) {
            throw new \InvalidArgumentException('Expecting relative path to file/sub-directory');
        }

        $pathToChild = $this->pathToChild($pathToChild);
        Filesystem::Chmod($mode, $pathToChild);
        Filesystem::ClearPathStatCache($pathToChild);
    }

    /**
     * Deletes itself or a child file or directory
     * @param string|null $pathToChild
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function delete(?string $pathToChild = null): void
    {
        if (!$this->isReadable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "Directory is not readable");
        }

        // Remove a file or subdirectory
        if ($pathToChild) {
            $childType = $this->contains($pathToChild);
            if (!$childType) {
                throw new FilesystemException(
                    FilesystemError::IO_ERROR,
                    "Cannot delete; Target file/sub-directory does not exist",
                );
            }

            if ($childType === PathType::DIRECTORY) {
                (new Directory($pathToChild))->delete(); // Remove subdirectory
                return;
            }

            if (!unlink($this->pathToChild($pathToChild))) {
                throw new FilesystemException(
                    FilesystemError::IO_ERROR,
                    sprintf('Failed to delete file "%s"', basename($pathToChild)),
                    data: [$pathToChild]
                );
            }

            return;
        }

        // Remove directory
        $this->flush(); // Delete all files and subdirectory
        if (!rmdir($this->pathIfExists())) {
            throw new FilesystemException(FilesystemError::IO_ERROR, "Failed to delete directory");
        }

        $this->deleted = true;
    }

    /**
     * Deletes all files and subdirectories inside this directory
     * @param bool $ignoreFails If TRUE then keeps deleting files even if one of the files has failed to delete
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function flush(bool $ignoreFails = false): int
    {
        if (!$this->isReadable()) {
            throw new FilesystemException(FilesystemError::PERMISSION_ERROR, "Cannot flush, Directory is not writable");
        }

        $deleted = 0;
        foreach ($this->scan(true) as $path) {
            if (is_dir($path)) {
                $deleted += (new Directory($path))->flush($ignoreFails);
                if (!rmdir($path)) {
                    if (!$ignoreFails) {
                        throw new FilesystemException(
                            FilesystemError::IO_ERROR,
                            sprintf('Could not delete sub-directory "%s"', basename($path)),
                            data: [$path]
                        );
                    }
                }

                continue;
            }

            if (!unlink($path)) {
                if ($ignoreFails) {
                    continue;
                }

                throw new FilesystemException(
                    FilesystemError::IO_ERROR,
                    sprintf('Could not delete file "%s"', basename($path)),
                    data: [$path]
                );
            }

            $deleted++;
        }

        return $deleted;
    }

    /**
     * Returns directory size (including all files and subdirectories) in bytes
     * @return int
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function findSizeInBytes(): int
    {
        $sizeInBytes = 0;
        $list = $this->scan(true);
        foreach ($list as $file) {
            if (is_dir($file)) {
                $sizeInBytes += (new Directory($file))->size();
                continue;
            }

            $fileSize = filesize($file);
            if (!is_int($fileSize)) {
                throw new FilesystemException(FilesystemError::IO_ERROR,
                    sprintf('Could not delete file "%s"', basename($file)),
                    data: [$file]);
            }

            $sizeInBytes += $fileSize;
        }

        return $sizeInBytes;
    }
}
