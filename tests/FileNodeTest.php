<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Filesystem\Node\FileNode;
use Charcoal\Filesystem\Node\PathInfo;

/**
 * Class FileTest
 * @package Charcoal\Filesystem\Tests
 */
class FileNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testReadFile(): void
    {
        $file = $this->getTestFile("some-file-1");
        $this->assertEquals("this", $file->read(0, 4));
        $this->assertEquals(" is a ", $file->read(4, 6));
        $this->assertEquals("test file", $file->read(10));
        $this->assertEquals("this is a test file", $file->read());
        $this->assertEquals(19, $file->readToBuffer()->len());
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function testFilesize(): void
    {
        $file = $this->getTestFile("some-file-1");
        $size = $file->size();
        $this->assertIsInt($size);
        $this->assertTrue(($size > 0));
    }

    /**
     * @param string $name
     * @return \Charcoal\Filesystem\Node\FileNode
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getTestFile(string $name): \Charcoal\Filesystem\Node\FileNode
    {
        return new FileNode(new PathInfo(__DIR__ . DIRECTORY_SEPARATOR .
                "data" . DIRECTORY_SEPARATOR . $name));
    }
}