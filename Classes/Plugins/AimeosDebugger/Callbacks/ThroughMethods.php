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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\ConstInterface;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as IterateThroughMethods;

/**
 * Simple wrapper around the original class.
 *
 * We need to preprocess the parameters, because we want to mass-dump
 * method analysis data from different source classes.
 *
 * @uses array $data
 *   An array of reflection methods.
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks
 */
class ThroughMethods extends AbstractCallback implements ConstInterface, CallbackConstInterface
{
    /**
     * Pre-processing parameters before using the original ThroughMethods analysis.
     *
     * @return string
     */
    public function callMe(): string
    {
        $this->dispatchStartEvent();

        $data = $this->parameters[static::PARAM_DATA];
        $isFactoryMethod = isset($this->parameters[static::PARAM_IS_FACTORY_METHOD]);
        $result = '';
        /** @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods $thoughMethods */
        $thoughMethods = $this->pool->createClass(IterateThroughMethods::class);

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($data as $factoryName => $reflectionMethod) {
            $params = [
                static::PARAM_DATA => [$reflectionMethod],
                static::PARAM_REF => $reflectionMethod->getDeclaringClass(),
            ];
            // We may not be able to use the method name here
            // @see ViewFactory
            if ($isFactoryMethod === true) {
                $params[static::PARAM_FACTORY_NAME] = $factoryName;
            }
            // Now, that we have set the reflection class, we can call the original.
            $result .= $thoughMethods->setParameters($params)->callMe();
        }

        return $result;
    }
}
