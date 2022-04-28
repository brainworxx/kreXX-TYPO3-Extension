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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;

/**
 * Array analysis methods.
 *
 * @uses array data
 *   The array want to iterate.
 * @uses bool multiline
 *   Do we need a multiline code generation?
 */
class ThroughArray extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface,
    ProcessConstInterface
{
    /**
     * Renders the expendable around the array analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->pool->render->renderSingeChildHr() . $this->dispatchStartEvent();

        // Are we dealing with multiline code generation?
        $multilineCodeGen = $this->parameters[static::PARAM_MULTILINE] ?
            static::CODEGEN_TYPE_ITERATOR_TO_ARRAY : static::CODEGEN_TYPE_PUBLIC;

        $recursionMarker = $this->pool->recursionHandler->getMarker();
        $array =& $this->parameters[static::PARAM_DATA];

        // Iterate through.
        foreach ($array as $key => &$value) {
            // We will not output our recursion marker.
            // Meh, the only reason for the recursion marker
            // in arrays is because of the $GLOBAL array, which
            // we will only render once.
            if ($key === $recursionMarker) {
                continue;
            }

            $output .= $this->pool->routing
                ->analysisHub($this->prepareModel($array, $key, $value, $multilineCodeGen));
        }

        return $output . $this->pool->render->renderSingeChildHr();
    }

    /**
     * @param array $array
     * @param $key
     * @param $value
     * @param string $multilineCodeGen
     * @return \Brainworxx\Krexx\Analyse\Model
     */
    protected function prepareModel(array $array, $key, &$value, string $multilineCodeGen): Model
    {
        /** @var Model $model */
        $model = $this->pool
            ->createClass(Model::class)
            ->setData($value)
            ->setCodeGenType($multilineCodeGen);

        if (!array_key_exists($key, $array)) {
            // Looks like we have an inaccessible array value here.
            $model->setCodeGenType(static::CODEGEN_TYPE_ARRAY_VALUES_ACCESS)
                ->setConnectorParameters(array_search($key, array_keys($array)));
        }

        if (is_string($key)) {
            $model->setName($this->pool->encodingService->encodeString($key))
                ->setConnectorType(static::CONNECTOR_ASSOCIATIVE_ARRAY);
        } else {
            $model->setName($key)->setConnectorType(static::CONNECTOR_NORMAL_ARRAY);
        }

        return $model;
    }
}
