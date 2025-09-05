<?php
/**
 * Part of the "charcoal-dev/filesystem" package.
 * @link https://github.com/charcoal-dev/filesystem
 */

declare(strict_types=1);

namespace Charcoal\Filesystem\Traits;

use Charcoal\Filesystem\Enums\Assert;
use Charcoal\Filesystem\Exceptions\PathAssertFailedException;
use Charcoal\Filesystem\Filesystem;

/**
 * Provides assertion functionality for filesystem paths.
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
        return Filesystem::assert($this, ...$asserts);
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