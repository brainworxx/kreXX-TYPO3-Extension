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
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     */
    public function __construct($pool)
    {
        $this->pool = $pool;
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $file
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
    public function readSourcecode($file, $highlight, $from, $to)
    {
        $result = '';
        if (is_readable($file)) {
            // Load content and add it to the backtrace.
            $contentArray = file($file);
            // Correct the value, in case we are exceeding the line numbers.
            if ($from < 0) {
                $from = 0;
            }
            if ($to > count($contentArray)) {
                $to = count($contentArray);
            }

            for ($currentLineNo = $from; $currentLineNo <= $to; $currentLineNo++) {
                if (isset($contentArray[$currentLineNo])) {
                    // Add it to the result.
                    $realLineNo = $currentLineNo + 1;

                    // Escape it.
                    $contentArray[$currentLineNo] = $this->pool->encodeString($contentArray[$currentLineNo], true);

                    if ($currentLineNo === $highlight) {
                        $result .= $this->pool->render->renderBacktraceSourceLine(
                            'highlight',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    } else {
                        $result .= $this->pool->render->renderBacktraceSourceLine(
                            'source',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    }
                } else {
                    // End of the file.
                    break;
                }
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
        static $cacheArray = array();
        $result = '';

        // Read the file into our cache array. We may need to reed  this file a
        // few times.
        if (empty($cacheArray[$filename])) {
            if (is_readable($filename)) {
                $cacheArray[$filename] = file($filename);
            } else {
                // Not readable!
                $cacheArray[$filename] = array();
            }
        }
        if ($from < 0) {
             $from = 0;
        }
        if ($to < 0) {
            $to = 0;
        }

        // Do we have enough lines in there?
        if (count($cacheArray[$filename]) > $to) {
            for ($currentLineNo = $from; $currentLineNo <= $to; $currentLineNo++) {
                $result .= $cacheArray[$filename][$currentLineNo];
            }
        }

        return $result;
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
        $result = '';
        // Is it readable and does it have any content?
        if (is_readable($path)) {
            $size = filesize($path);
            if ($size > 0) {
                $file = fopen($path, "r");
                $result = fread($file, $size);
                fclose($file);
            }
        }

        return $result;
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
        // Do some caching, so we check a file or dir only once!
        static $ops = array();
        static $dir = array();

        // Check the directory.
        if (!isset($dir[dirname($path)])) {
            $dir[dirname($path)]['canwrite'] = is_writable(dirname($path));
        }

        if (!isset($ops[$path])) {
            // We need to do some checking:
            $ops[$path]['append'] = is_file($path);
            $ops[$path]['canwrite'] = is_writable($path);
        }

        // Do the writing!
        if ($ops[$path]['append']) {
            if ($ops[$path]['canwrite']) {
                // Old file where we are allowed to write.
                file_put_contents($path, $string, FILE_APPEND);
            }
        } else {
            if ($dir[dirname($path)]['canwrite']) {
                // New file we can create.
                file_put_contents($path, $string);
                // We will append it on the next write attempt!
                $ops[$path]['append'] = true;
                $ops[$path]['canwrite'] = true;
            }
        }
    }

    /**
     * Returns the microtime timestamp for file operations.
     *
     * File operations are the logfiles and the chunk handling.
     *
     * @return string
     *   The timestamp itself.
     */
    public function fileStamp()
    {
        static $timestamp = 0;

        if (empty($timestamp)) {
            $timestamp = explode(" ", microtime());
            $timestamp = $timestamp[1] . str_replace("0.", "", $timestamp[0]);
        }

        return $timestamp;
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
            if (!unlink($filename)) {
                // We have a permission problem here!
                $this->pool->messages->addMessage('Unable to delete file: ' . $this->filterFilePath($filename));
            }

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
}
