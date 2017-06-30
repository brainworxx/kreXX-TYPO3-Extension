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

use Brainworxx\Krexx\Controller\AbstractController;
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
        if (empty($this->docRoot)) {
            $this->docRoot = false;
        }
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $filename
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
    public function readSourcecode($filename, $highlight, $readFrom, $readTo)
    {
        $result = '';

        // Read the file into our cache array. We may need to reed this file a
        // few times.
        $content = $this->getFileContentsArray($filename);

        if ($readFrom < 0) {
             $readFrom = 0;
        }
        if ($readTo < 0) {
            $readTo = 0;
        }

        for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
            if (isset($content[$currentLineNo])) {
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
     * @param int $readFrom
     * @param int $readTo
     *
     * @return string
     *   The content of the file, between the $from and $to.
     */
    public function readFile($filename, $readFrom = 0, $readTo = 0)
    {
        $result = '';

        // Read the file into our cache array.
        $content = $this->getFileContentsArray($filename);
        if ($readFrom < 0) {
             $readFrom = 0;
        }
        if ($readTo < 0) {
            $readTo = 0;
        }

        // Do we have enough lines in there?
        if (count($content) > $readTo) {
            for ($currentLineNo = $readFrom; $currentLineNo <= $readTo; ++$currentLineNo) {
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
     * @return \SplFixedArray
     *   The file in a \SplFixedArray.
     */
    protected function getFileContentsArray($filename)
    {
        static $filecache = array();

        if (isset($filecache[$filename])) {
            return $filecache[$filename];
        }

        // Using \SplFixedArray to save some memory, as it can get
        // quire huge, depending on your system. 4mb is nothing here.
        if ($this->fileIsReadable($filename)) {
            return $filecache[$filename] = \SplFixedArray::fromArray(file($filename));
        } else {
            // Not readable!
            return $filecache[$filename] = new \SplFixedArray(0);
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
        } else {
            // This file was not readable! We need to tell the user!
            $this->pool->messages->addMessage('fileserviceAccess', array($this->filterFilePath($path)));
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
        self::$isReadableCache[$path] = true;

    }

    /**
     * Tries to delete a file.
     *
     * @param string $filename
     */
    public function deleteFile($filename)
    {
        // Check if it is an actual file and if it is writable.
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
        $realpath = realpath($path);
        // File exist?
        if ($realpath === false) {
            $realpath = ltrim($path, DIRECTORY_SEPARATOR);
        } else {
            $realpath = ltrim($realpath, DIRECTORY_SEPARATOR);
        }

        if ($this->docRoot !== false && strpos($realpath, $this->docRoot) === 0) {
            // Found it on position 0.
            $realpath = '. . .' . DIRECTORY_SEPARATOR . substr($realpath, strlen($this->docRoot) + 1);
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
    protected function fileIsReadable($filePath)
    {
        // Return the cache, if we have any.
        if (isset(self::$isReadableCache[$filePath])) {
            return self::$isReadableCache[$filePath];
        }

        // Set the cache and return it.
        return self::$isReadableCache[$filePath] = is_readable($filePath) && is_file($filePath);
    }
}
