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

namespace Charcoal\Filesystem\Tests;

/**
 * Class DirectoryTest
 * @package Charcoal\Filesystem\Tests
 */
class DirectoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetChild(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertInstanceOf(\Charcoal\Filesystem\File::class, $dir->getChild("some-file-2"));
        $this->assertInstanceOf(\Charcoal\Filesystem\File::class, $dir->getChild("test-dir/some-file-3"));
        $this->assertInstanceOf(\Charcoal\Filesystem\Directory::class, $dir->getChild("test-dir"));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testChildDirectory(): void
    {
        $dir = $this->getTestDirectory();
        $childDir = $dir->getDirectory("test-dir");
        $this->assertInstanceOf(\Charcoal\Filesystem\Directory::class, $childDir);
        $readFile = $childDir->readFileToBuffer("some-file-3");
        $this->assertEquals("this is a third test file", $readFile->raw());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetNonExistingDirectory(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectExceptionCode(\Charcoal\Filesystem\Enums\FilesystemError::PATH_NOT_EXISTS->value);
        $dir->getDirectory("this-should-not-work");
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetFile(): void
    {
        $dir = $this->getTestDirectory();
        $file1 = $dir->getFile("some-file-2");
        $this->assertInstanceOf(\Charcoal\Filesystem\File::class, $file1);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetNonExistingFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectExceptionCode(\Charcoal\Filesystem\Enums\FilesystemError::PATH_NOT_EXISTS->value);
        $dir->getFile("this-should-not-work");
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testContains(): void
    {
        $dir = $this->getTestDirectory();
        $child1 = $dir->contains("some-file-2");
        $this->assertEquals(\Charcoal\Filesystem\Enums\PathType::FILE, $child1);
        $child2 = $dir->contains("non-existent");
        $this->assertNull($child2);
        $child3 = $dir->contains("test-dir");
        $this->assertEquals(\Charcoal\Filesystem\Enums\PathType::DIRECTORY, $child3);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testReadFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertEquals("this", $dir->readFile("some-file-1", 0, 4));
        $this->assertEquals(" is a ", $dir->readFile("some-file-1", 4, 6));
        $this->assertEquals("test file", $dir->readFile("some-file-1", 10));
        $this->assertEquals("this is a test file", $dir->readFile("some-file-1"));
        $this->assertEquals(19, $dir->readFileToBuffer("some-file-1")->len());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testReadChildDirFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertTrue(str_starts_with($dir->readFile("test-dir/some-file-3", 10), "third"));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testNonExistingFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectException(\Charcoal\Filesystem\Exceptions\FilesystemException::class);
        $dir->readFile("this-should-not-exist");
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testScan(): void
    {
        $dir = $this->getTestDirectory();
        $scan = $dir->scan();
        $this->assertIsArray($scan);
        $this->assertEquals("some-file-1", $scan[0]);
        $this->assertEquals("some-file-2", $scan[1]);
        $this->assertEquals("test-dir", $scan[2]);
    }

    /**
     * NOTE: windows returning absolute paths by default
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGlob(): void
    {
        $dir = $this->getTestDirectory();
        $glob = $dir->glob("some-file-*");
        $this->assertIsArray($glob);
        $this->assertEquals("some-file-1", basename($glob[0] ?? ""));
        $this->assertEquals("some-file-2", basename($glob[1] ?? ""));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testPermissions(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertIsBool($dir->isWritable());
        $this->assertIsBool($dir->isReadable());
        $this->assertIsBool($dir->isExecutable());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testSize(): void
    {
        $dir = $this->getTestDirectory();
        $size = $dir->size();
        $this->assertIsInt($size);
        $this->assertTrue(($size > 0));
    }

    /**
     * @return \Charcoal\Filesystem\Directory
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getTestDirectory(): \Charcoal\Filesystem\Directory
    {
        return new \Charcoal\Filesystem\Directory(__DIR__ . DIRECTORY_SEPARATOR . "data");
    }
}
