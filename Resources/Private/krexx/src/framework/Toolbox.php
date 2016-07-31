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

namespace Brainworxx\Krexx\Framework;

use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\View\Help;

/**
 * Toolbox methods
 *
 * @package Brainworxx\Krexx\Framework
 */
class Toolbox
{
    /**
     * Returns the microtime timestamp for file operations.
     *
     * File operations are the logfiles and the chunk handling.
     *
     * @return string
     *   The timestamp itself.
     */
    public static function fileStamp()
    {
        static $timestamp = 0;
        if ($timestamp == 0) {
            $timestamp = explode(" ", microtime());
            $timestamp = $timestamp[1] . str_replace("0.", "", $timestamp[0]);
        }

        return $timestamp;
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     *   TRUE when this is AJAX, FALSE if not
     */
    public static function isRequestAjaxOrCli()
    {
        if (Config::getConfigValue('output', 'destination') != 'file') {
            // When we are not going to create a logfile, we send it to the browser.
            // Check for ajax.
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                // Appending stuff after a ajax request will most likely
                // cause a js error. But there are moments when you actually
                // want to do this.
                if (Config::getConfigValue('runtime', 'detectAjax') == 'true') {
                    // We were supposed to detect ajax, and we did it right now.
                    return true;
                }
            }
            // Check for CLI.
            if (php_sapi_name() == "cli") {
                return true;
            }
        }
        // Still here? This means it's neither.
        return false;
    }

    /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is the object hash as well as the kruXX call number, to avoid
     * collisions (even if they are unlikely).
     *
     * @param mixed $data
     *   The object from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    public static function generateDomIdFromObject($data)
    {
        if (is_object($data)) {
            return 'k' . OutputActions::$KrexxCount . '_' . spl_object_hash($data);
        } else {
            // Do nothing.
            return '';
        }
    }

    /**
     * Simply outputs a formatted var_dump.
     *
     * This is an internal debugging function, because it is
     * rather difficult to debug a debugger, when your tool of
     * choice is the debugger itself.
     *
     * @param mixed $data
     *   The data for the var_dump.
     */
    public static function formattedVarDump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo('</pre>');
    }

    /**
     * Checks for a .htaccess file with a 'deny from all' statement.
     *
     * @param string $path
     *   The path we want to check.
     *
     * @return bool
     *   Whether the path is protected.
     */
    public static function isFolderProtected($path)
    {
        $result = false;
        if (is_readable($path . '/.htaccess')) {
            $content = file($path . '/.htaccess');
            foreach ($content as $line) {
                // We have what we are looking for, a
                // 'deny from all', not to be confuse with
                // a '# deny from all'.
                if (strtolower(trim($line)) == 'deny from all') {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Adds source sample to a backtrace.
     *
     * @param array $backtrace
     *   The backtrace from debug_backtrace().
     * @param int $offset
     *   When using the tick-backtrace, we are off by 1.
     *
     * @return array
     *   The backtrace with the source samples.
     */
    public static function addSourcecodeToBacktrace(array $backtrace, $offset = 0)
    {
        foreach ($backtrace as &$trace) {
            $trace['line'] = $trace['line'] + $offset;
            $source = self::readSourcecode($trace['file'], $trace['line'], $trace['line'] -5, $trace['line'] +5);
            // Add it only, if we have source code. Some internal functions do not
            // provide any (call_user_func for example).
            if (strlen(trim($source)) > 0) {
                $trace['sourcecode'] = $source;
            } else {
                $trace['sourcecode'] = Help::getHelp('noSourceAvailable');
            }
        }

        return $backtrace;
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $file
     *   Path to the file you want to read.
     * @param int $highlight
     *   The line number you want to highlight
     * @param int $from
     *   The strarline.
     * @param int $to
     *   The Endline.
     *
     * @return string
     *   The source code.
     */
    public static function readSourcecode($file, $highlight, $from, $to)
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
                    $contentArray[$currentLineNo] = self::encodeString($contentArray[$currentLineNo], true);

                    if ($currentLineNo == $highlight) {
                        $result .= OutputActions::$render->renderBacktraceSourceLine(
                            'highlight',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    } else {
                        $result .= OutputActions::$render->renderBacktraceSourceLine(
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
     * Removes the comment-chars from the comment string.
     *
     * @param string $comment
     *   The original comment from the reflection
     *   (or interface) in case if an inherited comment.
     *
     * @return string
     *   The better readable comment
     */
    public static function prettifyComment($comment)
    {
        // We split our comment into single lines and remove the unwanted
        // comment chars with the array_map callback.
        $commentArray = explode("\n", $comment);
        $result = array();
        foreach ($commentArray as $commentLine) {
            // We skip lines with /** and */
            if ((strpos($commentLine, '/**') === false) && (strpos($commentLine, '*/') === false)) {
                // Remove comment-chars, but we need to leave the whitespace intact.
                $commentLine = trim($commentLine);
                if (strpos($commentLine, '*') === 0) {
                    // Remove the * by char position.
                    $result[] = substr($commentLine, 1);
                } else {
                    // We are missing the *, so we just add the line.
                    $result[] = $commentLine;
                }
            }
        }

        return implode(PHP_EOL, $result);
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
    public static function getFileContents($path)
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
    public static function putFileContents($path, $string)
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
     * Return the current URL.
     *
     * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
     * @author Timo Huovinen
     *
     * @return string
     *   The current URL.
     */
    public static function getCurrentUrl()
    {
        static $result;

        if (!isset($result)) {
            $s = $_SERVER;

            // SSL or no SSL.
            if (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') {
                $ssl = true;
            } else {
                $ssl = false;
            }
            $sp = strtolower($s['SERVER_PROTOCOL']);
            $protocol = substr($sp, 0, strpos($sp, '/'));
            if ($ssl) {
                $protocol .= 's';
            }

            $port = $s['SERVER_PORT'];

            if ((!$ssl && $port == '80') || ($ssl && $port == '443')) {
                // Normal combo with port and protocol.
                $port = '';
            } else {
                // We have a special port here.
                $port = ':' . $port;
            }

            if (isset($s['HTTP_HOST'])) {
                $host = $s['HTTP_HOST'];
            } else {
                $host = $s['SERVER_NAME'] . $port;
            }

            $result = htmlspecialchars($protocol . '://' . $host . $s['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
        }
        return $result;
    }

    /**
     * Sanitizes a string, by completely encoding it.
     *
     * Should work with mixed encoding.
     *
     * @param string $data
     *   The data which needs to be sanitized.
     * @param bool $code
     *   Do we need to format the string as code?
     *
     * @return string
     *   The encoded string.
     */
    public static function encodeString($data, $code = false)
    {
        /**
         * List of all charsets that can be safely encoded via htmlentities().
         *
         * @var array
         */
        $charsetList = array(
            'UTF-8',
            'ISO-8859-1',
            'ISO-8859-5',
            'ISO-8859-15',
            'cp866',
            'cp1251',
            'Windows-1251',
            'cp1252',
            'Windows-1252',
            'KOI8-R',
            'koi8r',
            'BIG5',
            'GB2312',
            'Shift_JIS',
            'SJIS',
            'SJIS-win',
            'cp932',
            'EUC-JP',
            'EUCJP',
            'eucJP-win',
        );

        $result = '';
        // Try to encode it.
        $encoding = mb_detect_encoding($data, $charsetList);
        if ($encoding !== false) {
            set_error_handler(function () {
                /* do nothing. */
            });
            $result = @htmlentities($data, null, $encoding);
            restore_error_handler();
            // We are also encoding @, because we need them for our chunks.
            $result = str_replace('@', '&#64;', $result);
            // We ara also encoding the {, because we use it as markers for the skins.
            $result = str_replace('{', '&#123;', $result);
        }

        // Check if encoding was successful.
        if (strlen($result) === 0 && strlen($data) !== 0) {
            // Something went wrong with the encoding, we need to
            // completely encode this one to be able to display it at all!
            $data = @mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

            if ($code) {
                // We are displaying sourcecode, so we need
                // to do some formatting.
                $sortingCallback = function ($n) {
                    if ($n == 9) {
                        // Replace TAB with two spaces, it's better readable that way.
                        $result = '&nbsp;&nbsp;';
                    } else {
                        $result = "&#$n;";
                    }
                    return $result;
                };
            } else {
                // No formatting.
                $sortingCallback = function ($n) {
                    return "&#$n;";
                };
            }

            // Here we have another SPOF. When the string is large enough
            // we will run out of memory!
            // @see https://sourceforge.net/p/krexx/bugs/21/
            // We will *NOT* return the unescaped string. So we must check if it
            // is small enough for the unpack().
            // 100 kb should be save enough.
            if (strlen($data) < 102400) {
                $result = implode("", array_map($sortingCallback, unpack("N*", $data)));
            } else {
                $result = Help::getHelp('stringTooLarge');
            }
        } else {
            if ($code) {
                // Replace all tabs with 2 spaces to make sourcecode better
                // readable.
                $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
            }
        }

        return $result;
    }

    /**
     * The benchmark main function.
     *
     * @param array $timeKeeping
     *   The timekeeping array.
     *
     * @return array
     *   The benchmark array.
     *
     * @see http://php.net/manual/de/function.microtime.php
     * @author gomodo at free dot fr
     */
    public static function miniBenchTo(array $timeKeeping)
    {
        // Get the very first key.
        $start = key($timeKeeping);
        $totalTime = round((end($timeKeeping) - $timeKeeping[$start]) * 1000, 4);
        $result['url'] = Toolbox::getCurrentUrl();
        $result['total_time'] = $totalTime;
        $prevMomentName = $start;
        $prevMomentStart = $timeKeeping[$start];

        foreach ($timeKeeping as $moment => $time) {
            if ($moment != $start) {
                // Calculate the time.
                $percentageTime = round(((round(($time - $prevMomentStart) * 1000, 4) / $totalTime) * 100), 1);
                $result[$prevMomentName . '->' . $moment] = $percentageTime . '%';
                $prevMomentStart = $time;
                $prevMomentName = $moment;
            }
        }
        return $result;
    }
}
