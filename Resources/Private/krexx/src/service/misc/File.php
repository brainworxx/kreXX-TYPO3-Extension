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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Storage;

/**
 * File access service.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class File
{
    protected $storage;

    /**
     * Injects the storage.
     *
     * @param Storage $storage
     */
    public function __construct($storage)
    {
        $this->storage = $storage;
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
     *   The source code.
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
                    $contentArray[$currentLineNo] = $this->storage->encodeString($contentArray[$currentLineNo], true);

                    if ($currentLineNo === $highlight) {
                        $result .= $this->storage->render->renderBacktraceSourceLine(
                            'highlight',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    } else {
                        $result .= $this->storage->render->renderBacktraceSourceLine(
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
}
