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
    public function encodeString($data, $code = false)
    {
        // Try to encode it.
        set_error_handler(function () {
            /* do nothing. */
        });

        // We are also encoding @, because we need them for our chunks.
        // The { are needed in the marker of the skin.
        $result = str_replace(array('@', '{'), array('&#64;', '&#123;'), htmlentities($data));

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (empty($result)) {
            // Something went wrong with the encoding, we need to
            // completely encode this one to be able to display it at all!
            $data = mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

            if ($code) {
                // We are displaying sourcecode, so we need
                // to do some formatting.
                $sortingCallback = $sortingCallback = array($this, 'arrayMapCallbackCode');
            } else {
                // No formatting.
                $sortingCallback = array($this, 'arrayMapCallbackNormal');
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
                $result = $this->pool->messages->getHelp('stringTooLarge');
            }
        } else {
            if ($code) {
                // Replace all tabs with 2 spaces to make sourcecode better
                // readable.
                $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
            }
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
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
            $result = '&nbsp;&nbsp;';
        } else {
            $result = '&#' . $charCode . ';';
        }
        return $result;
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
