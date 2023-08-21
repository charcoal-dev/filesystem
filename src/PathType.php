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

namespace Charcoal\Filesystem;

/**
 * Class PathType
 * @package Charcoal\Filesystem
 */
enum PathType: int
{
    case FILE = 0x0a;
    case DIRECTORY = 0x14;
    case LINK = 0x1e;
}
