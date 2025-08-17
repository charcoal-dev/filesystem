<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Tests;

use Charcoal\Filesystem\Exceptions\NodeOpException;
use Charcoal\Filesystem\Exceptions\PathDeletedException;
use Charcoal\Filesystem\Exceptions\PathNotFoundException;
use Charcoal\Filesystem\Node\DirectoryNode;
use Charcoal\Filesystem\Node\FileNode;
use Charcoal\Filesystem\Path\PathInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class TmpDirectoryNodeTest
 * @package Charcoal\Filesystem\Tests
 */
final class TmpDirectoryNodeTest extends TestCase
{
    /**
     * @return void
     * @throws NodeOpException
     * @throws PathNotFoundException
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\PermissionException
     */
    public function testTmpDirectoryLifecycle(): void
    {
        // Ensure base tmp directory exists
        $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($basePath)) {
            self::assertTrue(mkdir($basePath, 0777, true), 'Failed to create tests/tmp directory');
        }

        $root = new DirectoryNode(new PathInfo($basePath));

        // 1) Flush everything first
        $deleted = $root->flush(true);
        $this->assertIsInt($deleted);
        $this->assertSame([], $root->scanDir(), 'tmp should be empty after flush');

        $ts = (string)(int)(microtime(true) * 1000);

        // 2) file() on non-existing (catch exception)
        $missingFile = "missing-$ts.txt";
        try {
            $root->file($missingFile, false);
            $this->fail('Expected PathNotFoundException for non-existing file');
        } catch (PathNotFoundException) {
            $this->assertTrue(true);
        }

        // 3) file() with createIfNotExists=true; then read/write
        $created = $root->file($missingFile, false, true);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(FileNode::class, $created);
        $this->assertSame($missingFile, basename($missingFile));

        $payload = "hello-$ts";
        $created->write($payload, append: false, lock: true);
        $this->assertSame($payload, $root->read($missingFile, true));

        $created->write("-world", append: true, lock: false);
        $this->assertSame($payload . "-world", $root->read($missingFile, true));

        // 4) Create deep directories (one go)
        $deepPath = "lvlA_$ts/lvlB_$ts/lvlC_$ts";
        $deepDir = $root->directory($deepPath, false, true);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(DirectoryNode::class, $deepDir);

        //    Create deep directories (one by one)
        $one = $root->directory("one_$ts", false, true);
        $two = $one->directory("two_$ts", false, true);
        $three = $two->directory("three_$ts", false, true);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(DirectoryNode::class, $three);

        //    Create a file inside the deepest level and verify read/write
        $nestedFileName = "note_$ts.txt";
        $nestedFile = $three->file($nestedFileName, false, true);
        $nestedPayload = "nested-$ts";
        $nestedFile->write($nestedPayload, append: false, lock: false);
        $this->assertSame($nestedPayload, $three->read($nestedFileName, true));

        // 5) scanDir + glob sanity checks
        $items = $root->scanDir();
        $this->assertContains("lvlA_$ts", $items);
        $this->assertContains("one_$ts", $items);

        $globTxt = $root->glob('*.txt');
        $this->assertContains($missingFile, array_map('basename', $globTxt));

        // 6) delete a file via deleteSelf (catch PathDeletedException and validate it preserves old PathInfo)
        try {
            $nestedFile->deleteSelf();
            /** @noinspection PhpUnreachableStatementInspection */
            $this->fail('Expected PathDeletedException from FileNode::deleteSelf');
        } catch (PathDeletedException $e) {
            $this->assertSame($nestedFile->path->absolute, $e->deleted->absolute, 'PathDeletedException carries old PathInfo');
            try {
                new FileNode(new PathInfo($e->deleted->absolute));
                $this->fail('After deletion the path should no longer be usable as FileNode');
            } catch (PathNotFoundException) {
                $this->assertTrue(true);
            }
        }

        // 7) delete directory via deleteSelf (catch PathDeletedException and validate it preserves old PathInfo)
        try {
            $deepDir->deleteSelf();
            /** @noinspection PhpUnreachableStatementInspection */
            $this->fail('Expected PathDeletedException from DirectoryNode::deleteSelf');
        } catch (PathDeletedException $e) {
            $this->assertSame($deepDir->path->absolute, $e->deleted->absolute, 'PathDeletedException carries old PathInfo');
            try {
                new DirectoryNode(new PathInfo($e->deleted->absolute));
                $this->fail('After deletion the path should no longer be usable as DirectoryNode');
            } catch (PathNotFoundException) {
                $this->assertTrue(true);
            }
        }

        // 8) Trigger a system error (suppressed with @) that becomes NodeOpException with RuntimeException as previous
        //    Strategy: hold a FileNode reference; delete its parent directory; then attempt writing (will fail at @file_put_contents).
        $brokenDir = $root->directory("broken_$ts", false, true);
        $brokenFile = $brokenDir->file("b_$ts.txt", false, true);
        $brokenFile->write("data-$ts", append: false, lock: false);

        try {
            $brokenDir->deleteSelf();
        } catch (PathDeletedException) {
            // Expected success signal for directory deletion
        }

        try {
            $brokenFile->write("more-$ts", append: false, lock: false);
            $this->fail('Expected NodeOpException due to missing parent dir');
        } catch (NodeOpException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious(),
                'NodeOpException should wrap last error as RuntimeException');
        }

        // 9) deleteChild for a simple file
        $toDelete = "to_delete_$ts.txt";
        $root->file($toDelete, false, true);
        $root->deleteChild($toDelete);
        try {
            $root->file($toDelete, true);
            $this->fail('Expected PathNotFoundException after deleteChild');
        } catch (PathNotFoundException) {
            $this->assertTrue(true);
        }
    }
}
