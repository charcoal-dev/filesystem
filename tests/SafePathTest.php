<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Filesystem\Enums\PathContext;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Path\SafePath;

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
            $for = SafePath::for($path, $context);
            var_dump($for);
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
        $this->assertEquals("var/log", SafePath::for("./var/log", $context)->path, "dot segment normalized");
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
        $this->assertEquals("foo", $this->invalidPathExceptionToFalse("./foo", $context), "dot+dir start normalized");
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

    /**
     * @return void
     */
    public function testEmptyAndWhitespacePaths(): void
    {
        $this->assertFalse($this->invalidPathExceptionToFalse("", PathContext::Unix), "empty path rejected (unix)");
        $this->assertFalse($this->invalidPathExceptionToFalse("   ", PathContext::Unix), "whitespace-only rejected (unix)");
        $this->assertFalse($this->invalidPathExceptionToFalse("", PathContext::Windows), "empty path rejected (windows)");
        $this->assertFalse($this->invalidPathExceptionToFalse("   ", PathContext::Windows), "whitespace-only rejected (windows)");
        $this->assertFalse($this->invalidPathExceptionToFalse("file://", PathContext::Scheme), "empty after scheme rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("file:/", PathContext::Scheme), "single-slash after scheme rejected");
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testUnixMoreEdgeCases(): void
    {
        $ctx = PathContext::Unix;

        // Normalize multiple leading/middle/trailing slashes
        $this->assertEquals("/etc/passwd", SafePath::for("///etc////passwd//", $ctx)->path);

        // Dot segments mid-path or terminal
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/./bar", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/../bar", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/.", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/..", $ctx));

        // Single dot or double dot as a path
        $this->assertFalse($this->invalidPathExceptionToFalse(".", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("..", $ctx));

        // Internal spaces and non-ascii (current rules: reject)
        $this->assertFalse($this->invalidPathExceptionToFalse("foo bar", $ctx), "space in segment rejected");
        $this->assertFalse($this->invalidPathExceptionToFalse("föö", $ctx), "non-ascii rejected by current pattern");

        // Trailing slash on absolute should normalize
        $this->assertEquals("/var/log", SafePath::for("/var/log/", $ctx)->path);
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testWindowsMoreEdgeCases(): void
    {
        $ctx = PathContext::Windows;

        // Lowercase drive + forward slashes normalized
        $this->assertEquals("c:\\Windows\\System32", SafePath::for("c:/Windows/System32", $ctx)->path);

        // Absolute with trailing slash normalizes
        $this->assertEquals("C:\\Windows", SafePath::for("C:\\Windows\\", $ctx)->path);

        // Forward slash relative normalizes to backslashes
        $this->assertEquals("foo\\bar", SafePath::for("foo/bar", $ctx)->path);

        // Root-only drive variants rejected
        $this->assertFalse($this->invalidPathExceptionToFalse("C:", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("C:\\", $ctx));

        // Dot segments in the middle rejected
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/./bar", $ctx));
        $this->assertFalse($this->invalidPathExceptionToFalse("foo/../bar", $ctx));

        // Illegal characters rejected (current rules)
        $this->assertFalse($this->invalidPathExceptionToFalse("foo*bar", $ctx));

        // UNC root/share normalization (accepted, transformed to relative)
        $this->assertEquals("server\\share", SafePath::for("\\\\server\\share\\", $ctx)->path);
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testSchemeMoreEdgeCases(): void
    {
        $ctx = PathContext::Scheme;

        // Backslashes are normalized in scheme inputs
        $this->assertEquals("file:///C:/Windows/System32", SafePath::for("file://C:\\Windows\\System32", $ctx)->path);

        // Non-empty host is rejected (current behavior)
        $this->assertFalse($this->invalidPathExceptionToFalse("file://localhost/etc/hosts", $ctx));

        // Case sensitivity of scheme prefix (current behavior: reject)
        $this->assertFalse($this->invalidPathExceptionToFalse("FILE://C:/Windows/System32", $ctx));

        // Must be absolute after the scheme
        $this->assertFalse($this->invalidPathExceptionToFalse("file:/etc/hosts", $ctx));
    }

    /**
     * @return void
     * @throws InvalidPathException
     */
    public function testFlagsAndSeparators(): void
    {
        // Unix
        $uAbs = SafePath::for("/etc/passwd", PathContext::Unix);
        $this->assertTrue($uAbs->absolute);
        $this->assertEquals("/", $uAbs->separator);
        $this->assertEquals(PathContext::Unix, $uAbs->context);

        $uRel = SafePath::for("var/log", PathContext::Unix);
        $this->assertFalse($uRel->absolute);
        $this->assertEquals("/", $uRel->separator);

        // Windows
        $wAbs = SafePath::for("C:\\Windows\\System32", PathContext::Windows);
        $this->assertTrue($wAbs->absolute);
        $this->assertEquals("\\", $wAbs->separator);
        $this->assertEquals(PathContext::Windows, $wAbs->context);

        // UNC becomes relative (by current design)
        $wUnc = SafePath::for("\\\\server\\share\\file.txt", PathContext::Windows);
        $this->assertFalse($wUnc->absolute);
        $this->assertEquals("\\", $wUnc->separator);

        // Scheme
        $sAbs = SafePath::for("file:///etc/hosts", PathContext::Scheme);
        $this->assertTrue($sAbs->absolute);
        $this->assertEquals("/", $sAbs->separator);
        $this->assertEquals(PathContext::Scheme, $sAbs->context);
    }
}