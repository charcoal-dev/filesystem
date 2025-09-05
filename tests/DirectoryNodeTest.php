<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathNotFoundException;
use Charcoal\Filesystem\Node\DirectoryNode;
use Charcoal\Filesystem\Node\FileNode;
use Charcoal\Filesystem\Path\PathInfo;

/**
 * Class DirectoryTest
 * @package Charcoal\Filesystem\Tests
 */
class DirectoryNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetChild(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertInstanceOf(FileNode::class, $dir->child("some-file-2", true));
        $this->assertInstanceOf(FileNode::class, $dir->child("test-dir/some-file-3", false));
        $this->assertInstanceOf(DirectoryNode::class, $dir->child("test-dir", true));

        $unitTestFile = new FileNode(new PathInfo(__FILE__));
        $this->assertEquals("DirectoryNodeTest.php", basename($unitTestFile->path->absolute));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testChildDirectory(): void
    {
        $dir = $this->getTestDirectory();
        $childDir = $dir->directory("test-dir", false);
        $buffer = new Buffer();
        $childDir->read("some-file-3", true, buffer: $buffer);
        $this->assertEquals("this is a third test file", $buffer->bytes());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetNonExistingDirectory(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectException(PathNotFoundException::class);
        $dir->directory("this-should-not-work", true);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetFile(): void
    {
        $dir = $this->getTestDirectory();
        $file2 = $dir->file("some-file-2", false);
        $this->assertEquals(PathType::File, $file2->path->type);
        $this->assertEquals("some-file-2", basename($file2->path->absolute));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testGetNonExistingFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectException(PathNotFoundException::class);
        $dir->file("this-should-not-work", false);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testContains(): void
    {
        $dir = $this->getTestDirectory();
        $child1 = $dir->child("some-file-2", false);
        $this->assertInstanceOf(FileNode::class, $child1);
        $child1 = $dir->child("some-file-2", true);
        $this->assertInstanceOf(FileNode::class, $child1);
        $child3 = $dir->child("test-dir", false);
        $this->assertInstanceOf(DirectoryNode::class, $child3);


        $this->expectException(PathNotFoundException::class);
        $dir->child("non-existent", false);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testReadFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertEquals("this", $dir->read("some-file-1", true, 0, 4));
        $this->assertEquals(" is a ", $dir->read("some-file-1", true, 4, 6));
        $this->assertEquals("test file", $dir->read("some-file-1", true, 10));
        $this->assertEquals("this is a test file", $dir->read("some-file-1", true));
        $buffer = new Buffer();
        $dir->read("some-file-2", true, buffer: $buffer);
        $this->assertEquals(25, $buffer->length());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testReadChildDirFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->assertTrue(str_starts_with($dir->read("test-dir/some-file-3", false, 10), "third"));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testNonExistingFile(): void
    {
        $dir = $this->getTestDirectory();
        $this->expectException(\Charcoal\Filesystem\Exceptions\FilesystemException::class);
        $dir->file("this-should-not-exist", false, false);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testScan(): void
    {
        $dir = $this->getTestDirectory();
        $scan = $dir->scanDir();
        $this->assertIsArray($scan);
        $this->assertEquals("some-file-1", $scan[0]);
        $this->assertEquals("some-file-2", $scan[1]);
        $this->assertEquals("test-dir", $scan[2]);
    }

    /**
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
    public function testSize(): void
    {
        $dir = $this->getTestDirectory();
        $size = $dir->size();
        $this->assertIsInt($size);
        $this->assertTrue(($size > 0));
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     */
    public function testChildPathInfoRelativeTrustedAndUntrusted(): void
    {
        $dir = $this->getTestDirectory();

        // File (relative)
        $piFileUntrusted = $dir->childPathInfo("some-file-2", false);
        $this->assertEquals(PathType::File, $piFileUntrusted->type);
        $this->assertEquals("some-file-2", basename($piFileUntrusted->absolute));

        $piFileTrusted = $dir->childPathInfo("some-file-2", true);
        $this->assertEquals(PathType::File, $piFileTrusted->type);
        $this->assertEquals("some-file-2", basename($piFileTrusted->absolute));
        $this->assertSame($piFileUntrusted->absolute, $piFileTrusted->absolute,
            'Trusted/untrusted produce the same absolute path for sane relative input');

        // Directory (relative)
        $piDirUntrusted = $dir->childPathInfo("test-dir", false);
        $this->assertEquals(PathType::Directory, $piDirUntrusted->type);

        $piDirTrusted = $dir->childPathInfo("test-dir", true);
        $this->assertEquals(PathType::Directory, $piDirTrusted->type);

        // Missing (relative)
        $missing = "missing-" . time();
        $piMissingUntrusted = $dir->childPathInfo($missing, false);
        $this->assertEquals(PathType::Missing, $piMissingUntrusted->type);

        $piMissingTrusted = $dir->childPathInfo($missing, true);
        $this->assertEquals(PathType::Missing, $piMissingTrusted->type);
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     */
    public function testChildPathInfoSanitizationAndTraversalDifferences(): void
    {
        $dir = $this->getTestDirectory();

        // Dot-segment path: rejected when untrusted, resolved when trusted
        try {
            $dir->childPathInfo("test-dir/./some-file-3", false);
            $this->fail('Expected InvalidPathException for untrusted path with dot-segment');
        } catch (\Charcoal\Filesystem\Exceptions\InvalidPathException) {
            $this->assertTrue(true);
        }
        $piDotTrusted = $dir->childPathInfo("test-dir/./some-file-3", true);
        $this->assertEquals(PathType::File, $piDotTrusted->type);
        $this->assertEquals("some-file-3", basename($piDotTrusted->absolute));

        // Parent-traversal: rejected when untrusted, resolved when trusted
        try {
            $dir->childPathInfo("../DirectoryNodeTest.php", false);
            $this->fail('Expected InvalidPathException for untrusted parent-traversal');
        } catch (\Charcoal\Filesystem\Exceptions\InvalidPathException) {
            $this->assertTrue(true);
        }
        $piParentTrusted = $dir->childPathInfo("../DirectoryNodeTest.php", true);
        $this->assertEquals(PathType::File, $piParentTrusted->type);
        $this->assertEquals("DirectoryNodeTest.php", basename($piParentTrusted->absolute));

        // Absolute injection: rejected when untrusted, allowed (but likely Missing) when trusted
        $absFile = $dir->path->absolute . DIRECTORY_SEPARATOR . "some-file-2";
        try {
            $dir->childPathInfo($absFile, false);
            $this->fail('Expected InvalidPathException for untrusted absolute path');
        } catch (\Charcoal\Filesystem\Exceptions\InvalidPathException) {
            $this->assertTrue(true);
        }

        $piAbsTrusted = $dir->childPathInfo($absFile, true);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(PathInfo::class, $piAbsTrusted);
        $this->assertEquals(PathType::Missing, $piAbsTrusted->type,
            'Trusted absolute is concatenated, typically resulting in a non-existing path');
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testChildPathInfoRejectsEmptyOrDotRegardlessOfTrust(): void
    {
        $dir = $this->getTestDirectory();

        foreach ([false, true] as $trusted) {
            try {
                $dir->childPathInfo("", $trusted);
                $this->fail('Expected InvalidPathException for empty child path');
            } catch (\Charcoal\Filesystem\Exceptions\InvalidPathException) {
                $this->assertTrue(true);
            }

            try {
                $dir->childPathInfo(".", $trusted);
                $this->fail('Expected InvalidPathException for "." child path');
            } catch (\Charcoal\Filesystem\Exceptions\InvalidPathException) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * @return DirectoryNode
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getTestDirectory(): DirectoryNode
    {
        return new DirectoryNode(new PathInfo(__DIR__ . DIRECTORY_SEPARATOR . "data"));
    }
}
