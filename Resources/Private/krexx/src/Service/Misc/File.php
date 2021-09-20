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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Factory\Pool;
use SplFixedArray;

/**
 * File access service.
 */
class File
{
    /**
     * Here we cache, if a file exists and is readable.
     *
     * @var bool[]
     */
    protected static $isReadableCache = [];

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * The current docroot.
     *
     * @var string|bool
     */
    protected $docRoot;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $server = $pool->getServer();
        $this->docRoot = trim($this->realpath($server['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);
        if (empty($this->docRoot) === true) {
            $this->docRoot = false;
        }
        $pool->fileService = $this;
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $filePath
     *   Path to the file you want to read.
     * @param int $highlight
     *   The line number you want to highlight
     * @param int $readFrom
     *   The start line.
     * @param int $readTo
     *   The end line.
     *
     * @return string
     *   The source code, HTML formatted.
     */
    public function readSourcecode(string $filePath, int $highlight, int $readFrom, int $readTo): string
    {
        $result = '';

        // Read the file into our cache array. We may need to reed this file a
        // few times.
        $content = $this->getFileContentsArray($filePath);

        if ($readFrom < 0) {
             $readFrom = 0;
        }

        if (isset($content[$readFrom]) === false) {
            // We can not even start reading this file!
            // Return empty string.
            return '';
        }

        if ($readTo < 0) {
            $readTo = 0;
        }

        if (isset($content[$readTo]) === false) {
            // We can not read this far, set it to the last line.
            $readTo = $content->count() - 1;
        }

        for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
            // Add it to the result.
            $realLineNo = $currentLineNo + 1;

            $currentLineNo === $highlight ? $className = 'highlight' : $className = 'source';
            $result .= $this->pool->render->renderBacktraceSourceLine(
                $className,
                $realLineNo,
                $this->pool->encodingService->encodeString($content[$currentLineNo], true)
            );
        }

        return $result;
    }

    /**
     * Simply read a file into a string.
     *
     * Used for source analysis.
     *
     * @param string $filePath
     * @param int $readFrom
     * @param int $readTo
     *
     * @return string
     *   The content of the file, between the $from and $to.
     */
    public function readFile(string $filePath, int $readFrom = 0, int $readTo = 0): string
    {
        $result = '';

        // Read the file into our cache array.
        $content = $this->getFileContentsArray($filePath);
        if ($readFrom < 0) {
             $readFrom = 0;
        }

        if ($readTo < 0) {
            $readTo = 0;
        }

        $countContent = $content->count();

        if ($countContent === 0) {
            return $result;
        }

        // Do we have enough lines in there?
        if ($countContent <= $readTo) {
            $readTo = $countContent - 1;
        }

        for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
            $result .= $content[$currentLineNo];
        }

        return $result;
    }

    /**
     * Reads a file into an array and uses some caching.
     *
     * @param string $filePath
     *   The path to the file we want to read.
     *
     * @return \SplFixedArray
     *   The file in a \SplFixedArray.
     */
    protected function getFileContentsArray(string $filePath): SplFixedArray
    {
        $filePath = $this->realpath($filePath);

        static $filecache = [];

        if (isset($filecache[$filePath]) === true) {
            return $filecache[$filePath];
        }

        // Using \SplFixedArray to save some memory, as it can get
        // quite huge, depending on your system. 4mb is nothing here.
        if ($this->fileIsReadable($filePath) === true) {
            return $filecache[$filePath] = SplFixedArray::fromArray(file($filePath));
        }
        // Not readable!
        return $filecache[$filePath] = new SplFixedArray(0);
    }

    /**
     * Reads the content of a file.
     *
     * Used to read kreXX resources and configuration into a string
     *
     * @param string $filePath
     *   The path to the file.
     * @param bool $showError
     *   Do we need to display na error message?
     *
     * @return string
     *   The content of the file, if readable.
     */
    public function getFileContents(string $filePath, bool $showError = true): string
    {
        if ($this->fileIsReadable($filePath) === false) {
            if ($showError === true) {
                // This file was not readable! We need to tell the user!
                $this->pool->messages->addMessage('fileserviceAccess', [$this->filterFilePath($filePath)], true);
            }
            // Return empty string.
            return '';
        }

        // Get the file contents.
        $filePath = $this->realpath($filePath);
        $size = filesize($filePath);
        $file = fopen($filePath, 'r');
        $result = fread($file, $size);
        fclose($file);
        return $result;
    }

    /**
     * Write the content of a string to a file.
     *
     * When the file already exists, we will append the content.
     * Caches whether we are allowed to write, to reduce the overhead.
     * Only used by the chunkes class, which tests beforehand, if we can write.
     *
     * @param string $filePath
     *   Path and filename.
     * @param string $string
     *   The string we want to write.
     */
    public function putFileContents(string $filePath, string $string)
    {
        // Register the file as a readable one.
        static::$isReadableCache[$filePath] = true;
        file_put_contents($filePath, $string, FILE_APPEND);
    }

    /**
     * Tries to delete a file.
     *
     * @param string $filePath
     */
    public function deleteFile(string $filePath)
    {
        $realpath = $this->realpath($filePath);

        set_error_handler(function () {
            /* do nothing */
        });

        // Fast-forward for the current chunk files.
        if (isset(static::$isReadableCache[$realpath]) === true) {
            unlink($realpath);
            restore_error_handler();
            return;
        }

        // Check if it is an actual file and if it is writable.
        // Those are left over chunks from previous calls, or old logfiles.
        if (is_file($realpath) === true) {
            // Make sure it is unlinkable.
            chmod($realpath, 0777);
            if (unlink($realpath) === false) {
                // We have a permission problem here!
                $this->pool->messages->addMessage('fileserviceDelete', [$this->filterFilePath($realpath)]);
            }
        }

        restore_error_handler();
    }

    /**
     * We will remove the $_SERVER['DOCUMENT_ROOT'] from the absolute
     * path of the calling file.
     * Return the original path, in case we can not determine the
     * $_SERVER['DOCUMENT_ROOT']
     *
     * @param string $filePath
     *   The path we want to filter
     *
     * @return string
     *   The filtered path to the calling file.
     */
    public function filterFilePath(string $filePath): string
    {
        $realpath = ltrim($this->realpath($filePath), DIRECTORY_SEPARATOR);
        if ($this->docRoot !== false && strpos($realpath, $this->docRoot) === 0) {
            // Found it on position 0.
            $realpath = '...' . DIRECTORY_SEPARATOR . substr($realpath, strlen($this->docRoot) + 1);
        }

        return $realpath;
    }

    /**
     * Checks if a file exists and is readable, with some caching.
     *
     * @param string $filePath
     *   The path to the file we are checking.
     *
     * @return bool
     *   If the file is readable, or not.
     */
    public function fileIsReadable(string $filePath): bool
    {
        $realPath = $this->realpath($filePath);

        // Return the cache, if we have any.
        if (isset(static::$isReadableCache[$realPath]) === true) {
            return static::$isReadableCache[$realPath];
        }

        // Set the cache and return it.
        return static::$isReadableCache[$realPath] = is_readable($realPath) && is_file($realPath);
    }

    /**
     * Just like filemtime(), but with some error handling.
     *
     * @param string $filePath
     *
     * @return int
     *   Timestamp of the file.
     */
    public function filetime(string $filePath): int
    {
        $filePath = $this->realpath($filePath);

        if ($this->fileIsReadable($filePath) === true) {
            set_error_handler(function () {
                // do nothing
            });
            $result = filemtime($filePath);
            restore_error_handler();
        }

        // Fallback to the current timestamp.
        // We are not interested in old file.
        // The current timestamp indicates, that this not-existing file is new.
        return empty($result) ? time() : $result;
    }

    /**
     * Wrapper around the native realpath method.
     *
     * When facing some special systems with strange configurations, realpath
     * might fail, although the file is right there.
     *
     * @param string $filePath
     *   Path to the file.
     *
     * @return string
     *   The real path, if possible. The original path as fallback
     */
    protected function realpath(string $filePath): string
    {
        $realpath = realpath($filePath);

        if ($realpath === false) {
            return $filePath;
        }

        return $realpath;
    }

    /**
     * Check if we can create and delete files in the specified directory.
     *
     * The php method is_writable is unreliable. We need to check ourselves.
     *
     * @param string $path
     *   The absolute directory path, ending with a '/'
     *
     * @return bool
     *   Well? Can we create and delete files in there?
     */
    public function isDirectoryWritable(string $path): bool
    {
        $filename = 'test';
        set_error_handler(function () {
            // do nothing
        });
        $result = (bool)file_put_contents($path . $filename, 'x') && unlink($path . $filename);
        restore_error_handler();

        return $result;
    }
}
