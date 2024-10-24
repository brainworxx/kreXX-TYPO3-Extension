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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use finfo;
use TypeError;

/**
 * Identifying a string as a file path.
 *
 * Adding a finfo analysis and a realpath if it differs from the given path.
 */
class FilePath extends AbstractScalarAnalysis
{
    /**
     * @var \finfo
     */
    protected finfo $bufferInfo;

    /**
     * @var string
     */
    protected const REAL_PATH = 'realpath';

    /**
     * @var string
     */
    protected const MIME_TYPE = 'mimetype';

    /**
     * No file path analysis without the finfo class.
     *
     * @return bool
     *   Is the finfo class available?
     */
    public static function isActive(): bool
    {
        return class_exists(finfo::class, false);
    }

    /**
     * Get the finfo class ready, if available.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->bufferInfo = new finfo(FILEINFO_MIME);
    }

    /**
     * Is this actually a path to a file? Simple wrapper around is_file().
     *
     * Of course, we only act, if finfo is available.
     *
     * @param string $string
     *   The string to test.
     *
     * @param Model $model
     *   The model, so far.
     *
     * @return bool
     *   The result, if it's callable.
     */
    public function canHandle($string, Model $model): bool
    {
        // Some fast static caching.
        static $cache = [];

        if (strlen($string) < 25) {
            // Early return for the most values.
            return false;
        }

        if (!isset($cache[$string])) {
            $cache[$string] = $this->retrieveFileInfo($string);
        }

        if (empty($cache[$string])) {
            // Not a file.
            return false;
        }

        $messages = $this->pool->messages;
        if (!empty($cache[$string][static::REAL_PATH])) {
            $model->addToJson($messages->getHelp('realPath'), $cache[$string][static::REAL_PATH]);
        }

        if (!empty($cache[$string][static::MIME_TYPE])) {
            $model->addToJson($messages->getHelp('metaMimeTypeFile'), $cache[$string][static::MIME_TYPE]);
        }

        return false;
    }

    /**
     * @param string $string
     * @return string[]
     */
    protected function retrieveFileInfo(string $string): array
    {
        $result = [];

        set_error_handler($this->pool->retrieveErrorCallback());
        try {
            $isFile = is_file($string);
        } catch (TypeError $exception) {
            $isFile = false;
        }
        restore_error_handler();

        if (!$isFile) {
            // Early return
            return $result;
        }

        $realPath = realpath($string);
        if ($string !== $realPath && is_string($realPath)) {
            // We only add the realpath, if it differs from the string
            $result[static::REAL_PATH] = $realPath;
        }
        $result[static::MIME_TYPE] = $this->bufferInfo->file($string);

        return $result;
    }
}
