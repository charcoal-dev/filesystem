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
 * Class FileTest
 * @package Charcoal\Filesystem\Tests
 */
class FileTest extends \PHPUnit\Framework\TestCase
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
     * @return \Charcoal\Filesystem\File
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getTestFile(string $name): \Charcoal\Filesystem\File
    {
        return new \Charcoal\Filesystem\File(__DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $name);
    }
}