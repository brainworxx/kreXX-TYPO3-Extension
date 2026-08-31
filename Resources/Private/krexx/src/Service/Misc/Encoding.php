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

/**
 * String encoding service.
 */
class Encoding
{
    /**
     * Injects the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
        $this->registerPolyfill();
        $pool->encodingService = $this;
    }

    /**
     * Register some namespaced cheap polyfills, in case the mb-string
     * extension is not available
     *
     * @codeCoverageIgnore
     *   We will not test a cheap polyfill.
     */
    protected function registerPolyfill(): void
    {
        if (!function_exists(function: 'mb_detect_encoding')) {
            /**
             * Cheap dummy "polyfill" for mb_detect_encoding
             *
             * @param string $string
             *   Will not get used.
             * @param string $encodings
             *   Will not get used.
             * @param bool $strict
             *   Will not get used.
             *
             * @return string
             *   Always 'polyfill'.
             */
            function mb_detect_encoding($string = '', $encodings = null, $strict = false): string|false
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
            function mb_strlen($string, $encoding = null): int
            {
                return strlen(string: (string) $string);
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
            function mb_substr($string, $start, $length): string
            {
                return substr(string: (string) $string, offset: $start, length: $length);
            }

            /**
             * The last cheap "polyfill". We only use this for displaying broken
             * strings,
             *
             * @param string $string
             *   Will not get used.
             * @param string $to_encoding
             *   Will not get used.
             * @param string $from_encoding
             *   Will not get used.
             *
             * @return string
             *   Always an empty string.
             */
            function mb_convert_encoding($string, $to_encoding, $from_encoding): string
            {
                return '';
            }

            // Tell the dev, that we have a problem.
            $this->pool->messages->addMessage(key: 'mbstringNotInstalled');
        }
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
    public function encodeString(string $data, bool $code = false): string
    {
        // We will not encode an empty string.
        if ($data === '') {
            return '';
        }

        if (strlen(string: $data) > 3072000) {
            // This is a very large string.
            // We would run out of memory, if we try to encode it.
            return $this->pool->messages->getHelp(key: 'stringTooLargeNormal');
        }

        // Initialize the encoding configuration.
        if ($code) {
            // We are encoding @, because we need them for our chunks.
            // The { are needed in the marker of the skin.
            // We also replace tabs with two nbsp's.
            $search = ['@', '{', chr(9)];
        } else {
            // We are encoding @, because we need them for our chunks.
            // The { are needed in the marker of the skin.
            $search = ['@', '{', '  '];
        }

        // There are several places here, that may throw a warning.
        set_error_handler(callback: $this->pool->retrieveErrorCallback());

        $result = str_replace(
            search: $search,
            replace: ['&#64;', '&#123;', '&nbsp;&nbsp;'],
            subject: htmlentities(string: $data, flags: ENT_QUOTES)
        );

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (empty($result)) {
            $result = $this->encodeCompletely(data: $data, code: $code);
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
    }

    /**
     * Something went wrong with the encoding, we need to completely encode
     * this one to be able to display it at all!
     *
     * Here we have another SPOF. When the string is large enough we will run
     * out of memory!
     * We will *NOT* return the unescaped string. So we must check if it is small
     * enough for the unpack() method. 100 kb should be safe enough.
     *
     * @param string $data
     *   The data which needs to be sanitized.
     * @param bool $code
     *   Do we need to format the string as code?
     *
     * @return string
     *   The encoded string.
     */
    protected function encodeCompletely(string &$data, bool $code): string
    {
        if (strlen(string: $data) > 102400) {
            return $this->pool->messages->getHelp(key: 'stringTooLarge');
        }

        $encoding = mb_detect_encoding(string: $data, encodings: 'auto', strict: true);
        $data = mb_convert_encoding(
            string: $data,
            to_encoding: 'UTF-32',
            from_encoding: $encoding === false ? null : $encoding
        );
        if (empty($data)) {
            // Unable to convert this string into something we can completely
            // encode. Fallback to an empty string.
            return '';
        }

        return implode(
            separator: '',
            array: array_map(
                callback: $code ? $this->arrayMapCallbackCode(...) : $this->arrayMapCallbackNormal(...),
                array: unpack(format: "N*", string: $data)
            )
        );
    }

    /**
     * Wrapper around mb_detect_encoding, to circumvent a not installed
     * mb_string php extension.
     *
     * @param string $string
     *   The string we want to analyse
     * @param string $encodinglist
     *   The ordered list of character encoding to check.
     * @param bool $strict
     *   Whether we want to use strict mode.
     *
     * @codeCoverageIgnore
     *   We will not test simple wrappers
     *
     * @return string|bool
     *   The result.
     */
    public function mbDetectEncoding(string $string): bool|string
    {
        return mb_detect_encoding(string: $string, encodings: 'auto', strict: true);
    }

    /**
     * Wrapper around mb_strlen, to circumvent a not installed
     * mb_string php extension.
     *
     * @param string $string
     *   The string we want to analyse
     * @param string|null $encoding
     *   The known encoding of the string, if known.
     *
     * @return int
     *   The result.
     */
    public function mbStrLen(string $string, ?string $encoding = null): int
    {
        // Meh, the original mb_strlen interprets a null here as an empty string.
        if ($encoding === null) {
            return mb_strlen(string: $string);
        }
        return mb_strlen(string: $string, encoding: $encoding);
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
     * @codeCoverageIgnore
     *   We will not test simple wrappers
     *
     * @return string
     *   The result.
     */
    public function mbSubStr(string $string, int $start, int $length): string
    {
        return mb_substr(string: $string, start: $start, length: $length);
    }

    /**
     * Encode a string for the code generation.
     *
     * Take care of quotes, null-strings and BOM stuff.
     * There are a lot of more invisible chars out there, but there is (afaik)
     * no fast way to detect and replace them all.
     * If anybody is actually reading this, and knows of a fast solution,
     * please open a ticket in our bug tracker.
     *
     * @param string|int $name
     *
     * @return string|int
     */
    public function encodeStringForCodeGeneration(string|int $name): string|int
    {
        if (is_int(value: $name)) {
            return $name;
        }

        $result = str_replace(
            search: ['&#039;', "\0", "\xEF", "\xBB", "\xBF"],
            replace: ["\&#039;", '\' . "\0" . \'', '\' . "\xEF" . \'', '\' . "\xBB" . \'', '\' . "\xBF" . \''],
            subject: $name
        );

        // Clean it up a bit
        return str_replace(search: '" . \'\' . "', replace: '', subject: $result);
    }

    /**
     * Callback for the complete escaping of strings.
     * Complete means every single char gets escaped.
     * This one dies some extra stuff for code display.
     *
     * @param int $charCode
     *
     * @return string
     *   The extra escaped result for code.
     */
    protected function arrayMapCallbackCode(int $charCode): string
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
     * @param int $charCode
     *
     * @return string
     *   The extra escaped result.
     */
    protected function arrayMapCallbackNormal(int $charCode): string
    {
        return '&#' . $charCode . ';';
    }
}
