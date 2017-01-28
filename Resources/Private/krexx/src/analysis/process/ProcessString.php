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

namespace Brainworxx\Krexx\Analyse\Process;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Processing of strings.
 *
 * @package Brainworxx\Krexx\Analyse\Process
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

        // Extra ?
        if (strlen($data) > 50) {
            $cut = substr($this->pool->encodeString($data), 0, 50) . '. . .';
            $model->hasExtras();
        } else {
            $cut = $this->pool->encodeString($data);
        }

        // We need to take care for mixed encodings here.
        $encoding = @mb_detect_encoding($data);
        $length = $strlen = @mb_strlen($data, $encoding);
        if ($strlen === false) {
            // Looks like we have a mixed encoded string.
            $length = '~ ' . strlen($data);
            $strlen = ' broken encoding ' . $length;
            $encoding = 'broken';
        }

        $data = $this->pool->encodeString($data);

        $model->setData($data)
            ->setNormal($cut)
            ->setType($model->getAdditional() . 'string' . ' ' . $strlen)
            ->addToJson('encoding', $encoding)
            ->addToJson('length', $length);

        // Check if this is a possible callback.
        // We are not going to analyse this further, because modern systems
        // do not use these anymore.
        if (is_callable($data)) {
            $model->setIsCallback(true);
        }

        return $this->pool->render->renderSingleChild($model);
    }
}