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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Service\Factory\Pool;
use finfo;

/**
 * Processing of strings.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessString extends AbstractRouting implements ProcessInterface
{
    /**
     * The buffer info class. We use it to get the mimetype from a string.
     *
     * @var finfo|\Brainworxx\Krexx\Service\Misc\FileinfoDummy
     */
    protected $bufferInfo;

    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Init the fileinfo class.
        if (class_exists('\\finfo', false) === true) {
            $this->bufferInfo = new finfo(FILEINFO_MIME);
        } else {
            // Use a "polyfill" dummy, tell the dev that we have a problem.
            $this->bufferInfo = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\FileinfoDummy');
            $pool->messages->addMessage('fileinfoNotInstalled');
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

        // Check if this is a possible callback.
        // We are not going to analyse this further, because modern systems
        // do not use these anymore.
        if (function_exists($data) === true) {
            $model->setIsCallback(true);
        }

        // Checking the encoding.
        $encoding = $this->pool->encodingService->mbDetectEncoding($data);
        if ($encoding === false) {
            // Looks like we have a mixed encoded string.
            // We need to tell the dev!
            $length = $this->pool->encodingService->mbStrLen($data);
            $strlen = 'broken encoding ' . $length;
            $model->addToJson(static::META_ENCODING, 'broken');
        } else {
            // Normal encoding, nothing special here.
            $length = $strlen = $this->pool->encodingService->mbStrLen($data, $encoding);
        }

        if ($length > 20) {
            // Getting mime type from the string.
            // With larger strings, there is a good chance that there is
            // something interesting in there.
            $model->addToJson(static::META_MIME_TYPE, $this->bufferInfo->buffer($data));
        }

        // Check, if we are handling large string, and if we need to use a
        // preview (which we call "extra").
        // We also need to check for linebreaks, because the preview can not
        // display those.
        if ($length > 50 || strstr($data, PHP_EOL) !== false) {
            $cut = $this->pool->encodingService->encodeString(
                $this->pool->encodingService->mbSubStr($data, 0, 50)
            ) . static::UNKNOWN_VALUE;

            $data = $this->pool->encodingService->encodeString($data);

            $model->setHasExtra(true)
                ->setNormal($cut)
                ->setData($data);
        } else {
            $model->setNormal($this->pool->encodingService->encodeString($data));
        }

        return $this->pool->render->renderSingleChild(
            $model->setType(static::TYPE_STRING . $strlen)
                ->addToJson(static::META_LENGTH, $length)
        );
    }
}
