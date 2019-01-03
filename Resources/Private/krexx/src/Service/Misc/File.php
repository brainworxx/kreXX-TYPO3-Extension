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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Misc;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * File access service.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class File
{

    /**
     * Here we cache, if a file exists and is readable.
     *
     * @var array
     */
    protected static $isReadableCache = array();

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * The current docroot.
     *
     * @var string|false
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
        $this->docRoot = trim(realpath($server['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);
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
    public function readSourcecode($filePath, $highlight, $readFrom, $readTo)
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
            $readTo = count($content) - 1;
        }

        for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
            // Add it to the result.
            $realLineNo = $currentLineNo + 1;

            if ($currentLineNo === $highlight) {
                $result .= $this->pool->render->renderBacktraceSourceLine(
                    'highlight',
                    $realLineNo,
                    $this->pool->encodingService->encodeString($content[$currentLineNo], true)
                );
            } else {
                $result .= $this->pool->render->renderBacktraceSourceLine(
                    'source',
                    $realLineNo,
                    $this->pool->encodingService->encodeString($content[$currentLineNo], true)
                );
            }
        }

        return $result;
    }

    /**
     * Simply read a file into a string.
     *
     * @param string $filePath
     * @param int $readFrom
     * @param int $readTo
     *
     * @return string
     *   The content of the file, between the $from and $to.
     */
    public function readFile($filePath, $readFrom = 0, $readTo = 0)
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

        $countContent = count($content);

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
    protected function getFileContentsArray($filePath)
    {
        $filePath = realpath($filePath);

        static $filecache = array();

        if (isset($filecache[$filePath]) === true) {
            return $filecache[$filePath];
        }

        // Using \SplFixedArray to save some memory, as it can get
        // quire huge, depending on your system. 4mb is nothing here.
        if ($this->fileIsReadable($filePath) === true) {
            return $filecache[$filePath] = \SplFixedArray::fromArray(file($filePath));
        }
        // Not readable!
        return $filecache[$filePath] = new \SplFixedArray(0);
    }

    /**
     * Reads the content of a file.
     *
     * @param string $filePath
     *   The path to the file.
     * @param boolean $showError
     *   Do we need to display na error message?
     *
     * @return string
     *   The content of the file, if readable.
     */
    public function getFileContents($filePath, $showError = true)
    {
        $filePath = realpath($filePath);

        if ($this->fileIsReadable($filePath) === false) {
            if ($showError === true) {
                // This file was not readable! We need to tell the user!
                $this->pool->messages->addMessage('fileserviceAccess', array($this->filterFilePath($filePath)));
            }
            // Return empty string.
            return '';
        }

        // Is it readable and does it have any content?
        $size = filesize($filePath);
        if ($size > 0) {
            $file = fopen($filePath, 'r');
            $result = fread($file, $size);
            fclose($file);
            return $result;
        }

        // Empty file returns an empty string.
        return '';
    }

    /**
     * Write the content of a string to a file.
     *
     * When the file already exists, we will append the content.
     * Caches weather we are allowed to write, to reduce the overhead.
     *
     * @param string $filePath
     *   Path and filename.
     * @param string $string
     *   The string we want to write.
     */
    public function putFileContents($filePath, $string)
    {
        if ($this->fileIsReadable($filePath) === true) {
            // Existing file. Most likely a html log file.
            file_put_contents($filePath, $string, FILE_APPEND);
            return;
        }

        // New file. We tell the caching, that we have read access here.
        file_put_contents($filePath, $string, FILE_APPEND);
        static::$isReadableCache[$filePath] = true;
    }

    /**
     * Tries to delete a file.
     *
     * @param string $filePath
     */
    public function deleteFile($filePath)
    {
        $filePath = realpath($filePath);

        // Check if it is an actual file and if it is writable.
        if (is_file($filePath) === true) {
            set_error_handler(
                function () {
                /* do nothing */
                }
            );
            // Make sure it is unlinkable.
            chmod($filePath, 0777);
            if (unlink($filePath) === true) {
                restore_error_handler();
                return;
            }

            // We have a permission problem here!
            $this->pool->messages->addMessage('fileserviceDelete', array($this->filterFilePath($filePath)));
            restore_error_handler();
        }
    }

    /**
     * We will remove the $_SERVER['DOCUMENT_ROOT'] from the absolute
     * path of the calling file.
     * Return the original path, in case we can not determine the
     * $_SERVER['DOCUMENT_ROOT']
     *
     * @param $filePath
     *   The path we want to filter
     *
     * @return string
     *   The filtered path to the calling file.
     */
    public function filterFilePath($filePath)
    {
        $realpath = realpath($filePath);
        // File exist?
        if ($realpath === false) {
            $realpath = ltrim($filePath, DIRECTORY_SEPARATOR);
        } else {
            $realpath = ltrim($realpath, DIRECTORY_SEPARATOR);
        }

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
    public function fileIsReadable($filePath)
    {
        $filePath = realpath($filePath);

        // Return the cache, if we have any.
        if (isset(static::$isReadableCache[$filePath]) === true) {
            return static::$isReadableCache[$filePath];
        }

        // Set the cache and return it.
        return static::$isReadableCache[$filePath] = is_readable($filePath) && is_file($filePath);
    }

    /**
     * Just like filemtime(), but with some error handling.
     *
     * @param string $filePath
     *
     * @return int
     *   Timestamp of the file.
     */
    public function filetime($filePath)
    {
        $filePath = realpath($filePath);

        if ($this->fileIsReadable($filePath)) {
            return filemtime($filePath);
        }

        // Fallback to the current timestamp.
        // We are not interested in old file.
        // The current timestamp indicates, that this not-existing file is new.
        return time();
    }
}
