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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Processing of strings.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessString extends AbstractProcess
{
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
        if ($encoding !== false) {
            // Normal encoding, nothing special here.
            $length = $strlen = mb_strlen($data, $encoding);
        } else {
            // Looks like we have a mixed encoded string.
            // We need to tell the dev!
            $length = mb_strlen($data);
            $strlen = ' broken encoding ' . $length;
            $encoding = 'broken';
        }

        // Check, if we are handling large string, and if we need to use a
        // preview (which we call "extra").
        // We also need to check for linebreaks, because the preview can not
        // display those.
        if ($length > 50 || strstr($data, PHP_EOL) !== false) {
            $cut = $this->pool->encodingService->encodeString(mb_substr($data, 0, 50)) . '. . .';
            $model->hasExtras();
        } else {
            $cut = $this->pool->encodingService->encodeString($data);
        }

        // Check if this is a possible callback.
        // We are not going to analyse this further, because modern systems
        // do not use these anymore.
        if (function_exists($data)) {
            $model->setIsCallback(true);
        }

        $data = $this->pool->encodingService->encodeString($data);
        return $this->pool->render->renderSingleChild(
            $model->setData($data)
                ->setNormal($cut)
                ->setType('string ' . $strlen)
                ->addToJson('encoding', $encoding)
                ->addToJson('length', $length)
        );
    }
}
