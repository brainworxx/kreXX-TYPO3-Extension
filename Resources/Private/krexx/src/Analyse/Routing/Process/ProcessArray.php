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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;

/**
 * Processing of arrays.
 */
class ProcessArray extends AbstractProcessNoneScalar implements
    ProcessConstInterface,
    CallbackConstInterface,
    ConfigConstInterface
{

    /**
     * Is this one an array?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this an array?
     */
    public function canHandle(Model $model): bool
    {
        return is_array($model->getData());
    }

    /**
     * Render a dump for an array.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    protected function handleNoneScalar(Model $model): string
    {
        $this->pool->emergencyHandler->upOneNestingLevel();
        $multiline = false;
        $count = count($model->getData());

        if ($count > (int) $this->pool->config->getSetting(static::SETTING_ARRAY_COUNT_LIMIT)) {
            // Budget array analysis.
            $model->injectCallback($this->pool->createClass(ThroughLargeArray::class))
                ->setHelpid('simpleArray');
        } else {
            // Complete array analysis.
            $model->injectCallback($this->pool->createClass(ThroughArray::class));
        }

        // Dumping all Properties.
        $result = $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $model->setType(static::TYPE_ARRAY)
                    ->setNormal($count . ' elements')
                    ->addParameter(static::PARAM_DATA, $model->getData())
                    ->addParameter(static::PARAM_MULTILINE, $multiline)
            )
        );

        $this->pool->emergencyHandler->downOneNestingLevel();
        return $result;
    }
}
