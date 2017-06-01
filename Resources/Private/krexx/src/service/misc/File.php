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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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
     * The cache for the reading of files.
     *
     * @var array
     */
    protected $fileCache = array();

    /**
     * Here we cache, if a file exists and is readable.
     *
     * @var array
     */
    protected $isReadableCache = array();

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $filename
     *   Path to the file you want to read.
     * @param int $highlight
     *   The line number you want to highlight
     * @param int $from
     *   The start line.
     * @param int $to
     *   The end line.
     *
     * @return string
     *   The source code, HTML formatted.
     */
    public function readSourcecode($filename, $highlight, $from, $to)
    {
        $result = '';

        // Read the file into our cache array. We may need to reed this file a
        // few times.
        $content = $this->getFileContentsArray($filename);

        if ($from < 0) {
             $from = 0;
        }
        if ($to < 0) {
            $to = 0;
        }

        for ($currentLineNo = $from; $currentLineNo <= $to; ++$currentLineNo) {
            if (isset($content[$currentLineNo])) {
                // Add it to the result.
                $realLineNo = $currentLineNo + 1;

                // Escape it.
                $content[$currentLineNo] = $this->pool->encodeString(
                    $content[$currentLineNo],
                    true
                );

                if ($currentLineNo === $highlight) {
                    $result .= $this->pool->render->renderBacktraceSourceLine(
                        'highlight',
                        $realLineNo,
                        $content[$currentLineNo]
                    );
                } else {
                    $result .= $this->pool->render->renderBacktraceSourceLine(
                        'source',
                        $realLineNo,
                        $content[$currentLineNo]
                    );
                }
            } else {
                // End of the file.
                break;
            }
        }

        return $result;
    }

    /**
     * Simply read a file into a string.
     *
     * @param string $filename
     * @param int $from
     * @param int $to
     *
     * @return string
     *   The content of the file, between the $from and $to.
     */
    public function readFile($filename, $from = 0, $to = 0)
    {
        $result = '';

        // Read the file into our cache array.
        $content = $this->getFileContentsArray($filename);
        if ($from < 0) {
             $from = 0;
        }
        if ($to < 0) {
            $to = 0;
        }

        // Do we have enough lines in there?
        if (count($content) > $to) {
            for ($currentLineNo = $from; $currentLineNo <= $to; ++$currentLineNo) {
                $result .= $content[$currentLineNo];
            }
        }

        return $result;
    }

    /**
     * Reads a file into an array and uses some caching.
     *
     * @param string $filename
     *   The path to the file we want to read.
     *
     * @return array
     *   The file array.
     */
    protected function getFileContentsArray($filename)
    {
        if (isset($this->fileCache[$filename])) {
            return $this->fileCache[$filename];
        }

        if ($this->fileIsReadable($filename)) {
            return $this->fileCache[$filename] = file($filename);
        } else {
            // Not readable!
            return $this->fileCache[$filename] = array();
        }
    }

    /**
     * Reads the content of a file.
     *
     * @param string $path
     *   The path to the file.
     *
     * @return string
     *   The content of the file, if readable.
     */
    public function getFileContents($path)
    {
        // Is it readable and does it have any content?
        if ($this->fileIsReadable($path)) {
            $size = filesize($path);
            if ($size > 0) {
                $file = fopen($path, 'r');
                $result = fread($file, $size);
                fclose($file);
                return $result;
            }
        }

        // This file was not readable! We need to tell the user!
        // Huh, we can not fully access this one.
        $this->pool->messages->addMessage('fileserviceAccess', array($this->filterFilePath($path)));
        return '';
    }

    /**
     * Write the content of a string to a file.
     *
     * When the file already exists, we will append the content.
     * Caches weather we are allowed to write, to reduce the overhead.
     *
     * @param string $path
     *   Path and filename.
     * @param string $string
     *   The string we want to write.
     */
    public function putFileContents($path, $string)
    {
        if ($this->fileIsReadable($path)) {
            // Existing file. Most likely a html log file.
            file_put_contents($path, $string, FILE_APPEND);
            return;
        }
        // New file. We tell the caching, that we have read access here.
        file_put_contents($path, $string, FILE_APPEND);
        $this->isReadableCache[$path] = true;

    }

    /**
     * Tries to delete a file.
     *
     * @param string $filename
     */
    public function deleteFile($filename)
    {
        // Check if it is an actual file and if it is writeable.
        if (is_file($filename)) {
            set_error_handler(function () {
                /* do nothing */
            });
            // Make sure it is unlinkable.
            chmod($filename, 0777);
            if (unlink($filename)) {
                restore_error_handler();
                return;
            }
            // We have a permission problem here!
            $this->pool->messages->addMessage('fileserviceDelete', array($this->filterFilePath($filename)));
            restore_error_handler();
        }
    }

    /**
     * We will remove the $_SERVER['DOCUMENT_ROOT'] from the absolute
     * path of the calling file.
     * Return the original path, in case we can not determine the
     * $_SERVER['DOCUMENT_ROOT']
     *
     * @param $path
     *   The path we want to filter
     *
     * @return string
     *   The filtered path to the calling file.
     */
    public function filterFilePath($path)
    {
        // There may or may not be a trailing '/'.
        // We remove it, just in case, to make sure that we remove the doc root
        // completely from the $path variable.
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        if (isset($docRoot) && strpos($path, $docRoot) === 0) {
            // Found it on position 0.
            $path = '. . ./' . substr($path, strlen($docRoot) + 1);
        }

        return $path;
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
    protected function fileIsReadable($filePath)
    {
        // Return the cache, if we have any.
        if (isset($this->isReadableCache[$filePath])) {
            return $this->isReadableCache[$filePath];
        }

        // Set the cache and return it.
        return $this->isReadableCache[$filePath] = is_readable($filePath) && is_file($filePath);
    }
}
