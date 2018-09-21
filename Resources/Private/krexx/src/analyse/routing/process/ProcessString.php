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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Processing of strings.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessString extends AbstractProcess
{
    /**
     * The buffer info class. We use it to get the mimetype from a string.
     *
     * @var \finfo|\Brainworxx\Krexx\Service\Misc\FileinfoDummy
     */
    protected $bufferInfo;

    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Init the fileinfo class.
        if (class_exists('\\finfo', false) === true) {
            $this->bufferInfo = new \finfo(FILEINFO_MIME);
        } else {
            // Use a "polyfill" dummy, tell the dev that we have a problem.
            $this->bufferInfo = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\FileinfoDummy');
            $pool->messages->addMessage('fileinfoNotInstalled');
        }

        // Register some namspaced cheap polifills, in case the mb-string
        // extension is not available
        if (function_exists('mb_detect_encoding') === false) {

            /**
             * Cheap dummy "polyfill" for mb_detect_encoding
             *
             * @param $string
             *   Will not get used.
             *
             * @return string
             *   Always 'polyfill'.
             */
            function mb_detect_encoding($string)
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
            function mb_strlen($string, $encoding)
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
             *   The length we want
             *
             * @return string
             *   The substring, according to substr().
             */
            function mb_substr($string, $start, $length)
            {
                return substr($string, $start, $length);
            }

            // Tell the dev, that we have a problem.
            $pool->messages->addMessage('mbstringNotInstalled');
        }
    }

    /**
     * Render a dump for a string value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function process(Model $model)
    {
        $data = $model->getData();

        // Checking the encoding.
        $encoding = mb_detect_encoding($data);
        if ($encoding === false) {
            // Looks like we have a mixed encoded string.
            // We need to tell the dev!
            $length = mb_strlen($data);
            $strlen = ' broken encoding ' . $length;
            $encoding = 'broken';
        } else {
            // Normal encoding, nothing special here.
            $length = $strlen = mb_strlen($data, $encoding);
            $encoding = '';
        }

        // Check if this is a possible callback.
        // We are not going to analyse this further, because modern systems
        // do not use these anymore.
        if (function_exists($data) === true) {
            $model->setIsCallback(true);
        }

        $mimetype = '';
        if ($length > 20) {
            // Getting mime type from the string.
            $mimetype = $this->bufferInfo->buffer($data);
        }

        // Check, if we are handling large string, and if we need to use a
        // preview (which we call "extra").
        // We also need to check for linebreaks, because the preview can not
        // display those.
        if ($length > 50 || strstr($data, PHP_EOL) !== false) {
            $cut = $this->pool->encodingService->encodeString(mb_substr($data, 0, 50)) . '. . .';
            $data = $this->pool->encodingService->encodeString($data);
            $model->setHasExtra(true)->setNormal($cut)->setData($data);
        } else {
            $model->setNormal($this->pool->encodingService->encodeString($data));
        }

        return $this->pool->render->renderSingleChild(
            $model->setType('string ' . $strlen)
                ->addToJson('encoding', $encoding)
                ->addToJson('mimetype', $mimetype)
                ->addToJson('length', $length)
        );
    }
}
