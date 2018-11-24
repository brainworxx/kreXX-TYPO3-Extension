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
 * String encoding service.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class Encoding
{
    /**
     * Our pool.
     *
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

        // Register some namspaced cheap polyfills, in case the mb-string
        // extension is not available
        if (function_exists('mb_detect_encoding') === false) {

            /**
             * Cheap dummy "polyfill" for mb_detect_encoding
             *
             * @param $string
             *   Will not get used.
             * @param $strict
             *   Will not get used.
             *
             * @return string
             *   Always 'polyfill'.
             */
            function mb_detect_encoding($string = '', $strict = '')
            {
                return 'polyfill';
            }

            /**
             * Cheap "polyfill" for mb_strlen.
             *
             * @param $string
             *   The sting we want to measure.
             * @param $encoding
             *   Will not get used.
             *
             * @return int
             *   The length, according to strlen();
             */
            function mb_strlen($string, $encoding = null)
            {
                return strlen($string);
            }

            /**
             * Cheap "polyfill" for mb_substr.
             *
             * @param $string
             *   The original string.
             * @param $start
             *   The start.
             * @param $length
             *   The length we want.
             *
             * @return string
             *   The substring, according to substr().
             */
            function mb_substr($string, $start, $length)
            {
                return substr($string, $start, $length);
            }

            /**
             * The last cheap "polyfill". We only use this for displaying broken
             * strings,
             *
             * @param string $string
             * @param string $toEncoding
             * @param string $fromEncoding
             *
             * @return string
             *   always an empty string.
             */
            function mb_convert_encoding($string, $toEncoding, $fromEncoding)
            {
                return '';
            }

            // Tell the dev, that we have a problem.
            $pool->messages->addMessage('mbstringNotInstalled');
        }
        $pool->encodingService = $this;
    }

    /**
     * Sanitizes a string, by completely encoding it.
     *
     * Should work with mixed encoding.
     *
     * @param string $data
     *   The data which needs to be sanitized.
     * @param boolean $code
     *   Do we need to format the string as code?
     *
     * @return string
     *   The encoded string.
     */
    public function encodeString($data, $code = false)
    {
        // We will not encode an empty string.
        if ($data === '') {
            return '';
        }

        // Initialize the encoding configuration.
        if ($code === true) {
            // We encoding @, because we need them for our chunks.
            // The { are needed in the marker of the skin.
            // We also replace tabs with two nbsp's.
            $sortingCallback = array($this, 'arrayMapCallbackCode');
            $search = array('@', '{', chr(9));
            $replace = array('&#64;', '&#123;', '&nbsp;&nbsp;');
        } else {
            // We encoding @, because we need them for our chunks.
            // The { are needed in the marker of the skin.
            $sortingCallback = array($this, 'arrayMapCallbackNormal');
            $search = array('@', '{');
            $replace = array('&#64;', '&#123;');
        }

        // There are several places here, that may throw a warning.
        set_error_handler(
            function () {
                // Do nothing.
            }
        );

        $result = str_replace($search, $replace, htmlentities($data));

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (empty($result) === true) {
            // Here we have another SPOF. When the string is large enough
            // we will run out of memory!
            // @see https://sourceforge.net/p/krexx/bugs/21/
            // We will *NOT* return the unescaped string. So we must check if it
            // is small enough for the unpack().
            // 100 kb should be save enough.
            if (strlen($data) > 102400) {
                $result = $this->pool->messages->getHelp('stringTooLarge');
            } else {
                // Something went wrong with the encoding, we need to
                // completely encode this one to be able to display it at all!
                $data = mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));
                $result = implode("", array_map($sortingCallback, unpack("N*", $data)));
            }
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
    }

    /**
     * Wrapper around mb_detect_encoding, to circumvent a not installed
     * mb_string php extension.
     *
     * @param string $string
     *   The string we want to analyse
     * @param string $encodinglist
     *   The orderd list of character encoding to check.
     * @param bool $strict
     *   Whether we want to use strict mode.
     *
     * @return string
     *   The result.
     */
    public function mbDetectEncoding($string, $encodinglist = 'auto', $strict = null)
    {
        return mb_detect_encoding($string, $encodinglist, $strict);
    }

    /**
     * Wrapper around mb_strlen, to circumvent a not installed
     * mb_string php extension.
     *
     * @param string $string
     *   The string we want to analyse
     * @param string $encoding
     *   The known encoding of the string, if known.
     *
     * @return integer
     *   The result.
     */
    public function mbStrLen($string, $encoding = null)
    {
        // Meh, the original mb_strlen interprets a null here as an empty string.
        if ($encoding === null) {
            return mb_strlen($string);
        }
        return mb_strlen($string, $encoding);
    }

    /**
     * Wrapper around mb_substr, to circumvent a not installed
     * mb_string php extension.
     *
     * @param string $string
     *   The string we want to analyse
     * @param int $start
     *   The starting point.
     * @param int $length
     *   The length we want.
     *
     * @return string
     *   The result.
     */
    public function mbSubStr($string, $start, $length)
    {
        return mb_substr($string, $start, $length);
    }

    /**
     * Callback for the complete escaping of strings.
     * Complete means every single char gets escaped.
     * This one dies some extra stuff for code display.
     *
     * @param integer $charCode
     *
     * @return string
     *   The extra escaped result for code.
     */
    protected function arrayMapCallbackCode($charCode)
    {
        if ($charCode === 9) {
            // Replace TAB with two spaces, it's better readable that way.
            return '&nbsp;&nbsp;';
        }
        return '&#' . $charCode . ';';
    }

    /**
     * Callback for the complete escaping of strings.
     * Complete means every single char gets escaped.
     *
     * @param integer $charCode
     *
     * @return string
     *   The extra escaped result.
     */
    protected function arrayMapCallbackNormal($charCode)
    {
        return '&#' . $charCode . ';';
    }
}
