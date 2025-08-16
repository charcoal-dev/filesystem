<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\SafePath;

/**
 * Class SafePathTest
 * @package Charcoal\Filesystem\Tests
 */
class SafePathTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $path
     * @param PathContext $context
     * @return bool
     */
    public function invalidPathExceptionToFalse(string $path, PathContext $context): bool
    {
        try {
            SafePath::for($path, $context);
            return true;
        } catch (InvalidPathException) {
            return false;
        }
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testSafePathUnix(): void
    {
        $context = PathContext::Unix;

        // $context = PathContext::Unix
        $this->assertEquals("/etc/passwd", SafePath::for("/etc/passwd", $context)->path, "absolute POSIX path");
        $this->assertEquals("var/log", SafePath::for("var/log/", $context)->path, "trailing slash removed");
        $this->assertEquals("foo/bar", SafePath::for("foo//bar", $context)->path, "collapses multiple slashes");
        $this->assertEquals(".env", SafePath::for(".env", $context)->path, "dotfile allowed");
        $this->assertFalse($this->invalidPathExceptionToFalse("/", $context), "root-only is invalid");
        $this->assertFalse($this->invalidPathExceptionToFalse("./var/log", $context), "dot segment rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("../var/log", $context), "parent traversal rejected");

        // Windows & Scheme given Unix context (should reject)
        $this->assertFalse($this->invalidPathExceptionToFalse("C:\\Windows\\System32", $context),
            "windows path rejected in unix context");
        $this->assertFalse($this->invalidPathExceptionToFalse("file:///etc/hosts", $context),
            "scheme path rejected in unix context");
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testSafePathWindows(): void
    {
        $context = PathContext::Windows;

        $this->assertEquals("C:\\Windows\\System32", SafePath::for("C:\\Windows\\System32", $context)->path,
            "windows absolute with backslashes");
        $this->assertEquals("C:\\Windows\\System32", SafePath::for("C:/Windows/System32", $context)->path,
            "windows absolute with forward slashes normalized");
        $this->assertEquals("foo\\bar", SafePath::for("foo\\bar\\", $context)->path, "trailing backslash removed");
        $this->assertEquals("foo\\bar", SafePath::for("foo//bar", $context)->path,
            "double forward slashes collapsed and converted");
        $this->assertEquals("server\\share\\file.txt", SafePath::for("\\\\server\\share\\file.txt", $context)->path,
            "UNC accepted but transformed to relative (by design right now)");

        $this->assertFalse($this->invalidPathExceptionToFalse("/", $context),
            "unix root rejected in windows context");
        $this->assertFalse($this->invalidPathExceptionToFalse("C:", $context),
            "drive without slash rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("./foo", $context),
            "dot segment rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("../foo", $context),
            "parent traversal rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("file:///C:/Windows/System32", $context),
            "scheme path rejected in windows context");
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testSafePathScheme(): void
    {
        $context = PathContext::Scheme;
        $this->assertEquals("file:///C:/Windows/System32", SafePath::for("file://C:/Windows/System32", $context)->path,
            "scheme + windows absolute OK");
        $this->assertEquals("file:///etc/hosts", SafePath::for("file:///etc/hosts", $context)->path,
            "scheme + posix absolute keeps triple slash");
        $this->assertFalse($this->invalidPathExceptionToFalse("file://var/log", $context),
            "relative after scheme rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("file://../etc/passwd", $context),
            "
        traversal after scheme rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("file://../etc/passwd", $context),
            "parent traversal under scheme rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("file://var/log", $context),
            "relative after scheme rejected (absolute required)");
        $this->assertFalse($this->invalidPathExceptionToFalse("http://example.com/path", $context),
            "non-file schemes rejected");
    }
}