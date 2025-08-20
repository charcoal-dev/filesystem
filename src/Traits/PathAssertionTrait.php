<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Traits;

use Charcoal\Base\Support\Helpers\EnumHelper;
use Charcoal\Filesystem\Enums\Assert;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\PathAssertFailedException;
use Charcoal\Filesystem\Node\AbstractNode;
use Charcoal\Filesystem\Path\PathInfo;

/**
 * Trait PathAssertionTrait
 * @package Charcoal\Filesystem\Traits
 */
trait PathAssertionTrait
{
    /**
     * @param Assert ...$asserts
     * @return int
     * @throws PathAssertFailedException
     */
    public function assert(Assert ...$asserts): int
    {
        $path = match (true) {
            $this instanceof AbstractNode => $this->path,
            $this instanceof PathInfo => $this,
            default => throw new \InvalidArgumentException("Invalid path type"),
        };

        $assertions = EnumHelper::filterUniqueFromSet(...$asserts);
        $passed = 0;
        if ($assertions) {
            foreach ($assertions as $assertion) {
                $test = match ($assertion) {
                    Assert::Exists => $path->type !== PathType::Missing,
                    Assert::Readable => $path->readable,
                    Assert::Writable => $path->writable,
                    Assert::Executable => $path->executable,
                    Assert::IsFile => $path->type === PathType::File,
                    Assert::IsDirectory => $path->type === PathType::Directory,
                };

                if (!$test) {
                    throw new PathAssertFailedException($path, "Assertion failed: " . $assertion->name);
                }
            }
        }

        return $passed;
    }

    /**
     * @param Assert ...$asserts
     * @return bool
     */
    public function assertQuite(Assert ...$asserts): bool
    {
        try {
            $this->assert(...$asserts);
            return true;
        } catch (PathAssertFailedException) {
            return false;
        }
    }
}