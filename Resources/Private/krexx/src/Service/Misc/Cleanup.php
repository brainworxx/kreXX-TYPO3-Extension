<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Misc;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Removing leftover chunks and old logfiles.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class Cleanup
{
    /**
     * The pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * We only do a chunks cleanup once.
     *
     * @var bool
     */
    protected static $chunksDone = false;

    /**
     * Assigning the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Deletes old logfiles.
     *
     * @return $this
     *   For chaining.
     */
    public function cleanupOldLogs(): Cleanup
    {
        if ($this->pool->chunks->getLoggingIsAllowed() === false) {
            // We have no write access. Do nothing.
            return $this;
        }

        // Cleanup old logfiles to prevent a overflow.
        $logList = glob($this->pool->config->getLogDir() . '*.Krexx.html');
        if (empty($logList) === true) {
            return $this;
        }

        array_multisort(
            array_map([$this->pool->fileService, 'filetime'], $logList),
            SORT_DESC,
            $logList
        );

        $maxFileCount = (int)$this->pool->config->getSetting(Fallback::SETTING_MAX_FILES);
        $count = 1;
        // Cleanup logfiles.
        foreach ($logList as $file) {
            if ($count > $maxFileCount) {
                $this->pool->fileService->deleteFile($file);
                $this->pool->fileService->deleteFile($file . '.json');
            }

            ++$count;
        }

        return $this;
    }

    /**
     * Deletes chunk files older then 1 hour, in case there are some left.
     *
     * @return $this
     *   For chaining.
     */
    public function cleanupOldChunks(): Cleanup
    {
        // Check for write access. We also do this only once.
        if (static::$chunksDone === true || $this->pool->chunks->getChunksAreAllowed() === false) {
            return $this;
        }

        static::$chunksDone = true;
        // Clean up leftover files.
        $chunkList = glob($this->pool->config->getChunkDir() . '*.Krexx.tmp');
        if (empty($chunkList) === true) {
            return $this;
        }

        $now = time();
        foreach ($chunkList as $file) {
            // We delete everything that is older than 15 minutes.
            if (($this->pool->fileService->filetime($file) + 900) < $now) {
                $this->pool->fileService->deleteFile($file);
            }
        }

        return $this;
    }
}
