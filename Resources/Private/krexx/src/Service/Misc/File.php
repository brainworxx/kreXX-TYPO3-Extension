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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
    protected static array $isReadableCache = [];

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
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
        $content = $this->getFileContentsArray(filePath: $filePath);

        if ($readFrom < 0) {
             $readFrom = 0;
        }

        if (!isset($content[$readFrom])) {
            // We can not even start reading this file!
            // Return empty string.
            return '';
        }

        if ($readTo < 0) {
            $readTo = 0;
        }

        if (!isset($content[$readTo])) {
            // We can not read this far, set it to the last line.
            $readTo = $content->count() - 1;
        }

        for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
            // Add it to the result.
            $realLineNo = $currentLineNo + 1;

            $currentLineNo === $highlight ? $className = 'highlight' : $className = 'source';
            $result .= $this->pool->render->renderBacktraceSourceLine(
                className: $className,
                lineNo: $realLineNo,
                sourceCode: $this->pool->encodingService->encodeString(data: $content[$currentLineNo], code: true)
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
        $content = $this->getFileContentsArray(filePath: $filePath);
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
     * @return \SplFixedArray <string>
     *   The file in a \SplFixedArray.
     */
    protected function getFileContentsArray(string $filePath): SplFixedArray
    {
        $filePath = $this->realpath(filePath: $filePath);

        static $filecache = [];

        if (isset($filecache[$filePath])) {
            return $filecache[$filePath];
        }

        // Using \SplFixedArray to save some memory, as it can get
        // quite huge, depending on your system. 4mb is nothing here.
        if ($this->fileIsReadable(filePath: $filePath)) {
            return $filecache[$filePath] = SplFixedArray::fromArray(array: file(filename: $filePath));
        }
        // Not readable!
        return $filecache[$filePath] = new SplFixedArray(size: 0);
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
        if (!$this->fileIsReadable(filePath: $filePath)) {
            if ($showError) {
                // This file was not readable! We need to tell the user!
                $this->pool->messages->addMessage(key: 'fileserviceAccess', args: [$filePath], isThrowAway: true);
            }
            // Return empty string.
            return '';
        }

        // Get the file contents.
        set_error_handler(callback: $this->pool->retrieveErrorCallback());
        $filePath = $this->realpath(filePath: $filePath);
        $file = fopen($filePath, 'r');
        if ($file === false) {
            // File opening just failed!
            $this->pool->messages->addMessage(key: 'fileserviceAccess', args: [$filePath], isThrowAway: true);
            restore_error_handler();
            return '';
        }
        $result = fread(stream: $file, length: filesize($filePath));
        fclose(stream: $file);
        restore_error_handler();

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
    public function putFileContents(string $filePath, string $string): void
    {
        // Register the file as a readable one.
        static::$isReadableCache[$filePath] = true;
        file_put_contents(filename: $filePath, data: $string, flags: FILE_APPEND);
    }

    /**
     * Tries to delete a file.
     *
     * @param string $filePath
     */
    public function deleteFile(string $filePath): void
    {
        $realpath = $this->realpath(filePath: $filePath);

        set_error_handler(callback: $this->pool->retrieveErrorCallback());

        // Fast-forward for the current chunk files.
        if (isset(static::$isReadableCache[$realpath])) {
            unlink(filename: $realpath);
            restore_error_handler();
            return;
        }

        // Check if it is an actual file and if it is writable.
        // Those are left over chunks from previous calls, or old logfiles.
        if (is_file(filename: $realpath)) {
            // Make sure it is unlinkable.
            chmod(filename: $realpath, permissions: 0777);
            if (!unlink(filename: $realpath)) {
                // We have a permission problem here!
                $this->pool->messages->addMessage(key: 'fileserviceDelete', args: [$realpath]);
            }
        }

        restore_error_handler();
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
        $realPath = $this->realpath(filePath: $filePath);

        // Set the cache and return it.
        return static::$isReadableCache[$realPath] ?? static::$isReadableCache[$realPath] = is_readable(filename: $realPath) && is_file(filename: $realPath);
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
        $filePath = $this->realpath(filePath: $filePath);

        if ($this->fileIsReadable(filePath: $filePath)) {
            set_error_handler(callback: $this->pool->retrieveErrorCallback());
            $result = filemtime(filename: $filePath);
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
        $realpath = realpath(path: $filePath);

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
        set_error_handler(callback: $this->pool->retrieveErrorCallback());
        $result = (bool)file_put_contents(filename: $path . $filename, data: 'x')
            && unlink(filename: $path . $filename);
        restore_error_handler();

        return $result;
    }
}
