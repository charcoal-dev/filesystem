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
        $assertions = EnumHelper::filterUniqueFromSet(...$asserts);
        $passed = 0;
        if ($assertions) {
            foreach ($assertions as $assertion) {
                $test = match ($assertion) {
                    Assert::Exists => $this->type !== PathType::Missing,
                    Assert::Readable => $this->readable,
                    Assert::Writable => $this->writable,
                    Assert::Executable => $this->executable,
                    Assert::IsFile => $this->type === PathType::File,
                    Assert::IsDirectory => $this->type === PathType::Directory,
                };

                if (!$test) {
                    throw new PathAssertFailedException($this, "Assertion failed: " . $assertion->name);
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