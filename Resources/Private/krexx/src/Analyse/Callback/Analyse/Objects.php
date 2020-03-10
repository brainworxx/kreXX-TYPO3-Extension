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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Constants;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ErrorObject;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PrivateProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ProtectedProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Throwable;

/**
 * Object analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse
 *
 * @uses object data
 *   The class we are analysing.
 * @uses string name
 *   The key of the class from the object/array holding this one.
 */
class Objects extends AbstractCallback
{

    /**
     * Starts the dump of an object.
     *
     * @throws \ReflectionException
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->pool->render->renderSingeChildHr() . $this->dispatchStartEvent();

        foreach ($this->generateDumperList() as $classname) {
            $output .= $this->pool
                ->createClass($classname)
                ->setParameters($this->parameters)
                ->callMe();
        }

        // Dumping all configured debug functions.
        // Adding a HR for a better readability.
        return $output . $this->pool->render->renderSingeChildHr();
    }

    /**
     * Generate a list of classes that will analyse the object.
     *
     * @throws \ReflectionException
     *
     * @return array
     *   The list with class names for the analysis.
     */
    protected function generateDumperList()
    {
        $ref = $this->parameters[static::PARAM_REF] = new ReflectionClass($this->parameters[static::PARAM_DATA]);
        $isInScope = $this->pool->scope->isInScope();
        $config = $this->pool->config;

        $stuffToDump = [PublicProperties::class];

        if ($ref->getName() === \stdClass::class) {
            // We ignore everything else for the stdClass..
            return $stuffToDump;
        }

        // Dumping getter methods.
        // We will not dump the getters for internal classes, though.
        if ($config->getSetting(Fallback::SETTING_ANALYSE_GETTER) === true && $ref->isUserDefined() === true) {
            $stuffToDump[] = Getter::class;
        }

        $stuffToDump[] = Meta::class;

        // Analysing error objects.
        if (is_a($this->parameters[static::PARAM_DATA], Throwable::class)) {
            $stuffToDump[] = ErrorObject::class;
        }

        // Dumping protected properties.
        if ($isInScope === true || $config->getSetting(Fallback::SETTING_ANALYSE_PROTECTED) === true) {
            $stuffToDump[] = ProtectedProperties::class;
        }

        // Dumping private properties.
        if ($isInScope === true || $config->getSetting(Fallback::SETTING_ANALYSE_PRIVATE) === true) {
            $stuffToDump[] = PrivateProperties::class;
        }

        // Dumping class constants.
        $stuffToDump[] = Constants::class;

        // Dumping all methods.
        $stuffToDump[] = Methods::class;

        // Dumping traversable data.
        if (
            $config->getSetting(Fallback::SETTING_ANALYSE_TRAVERSABLE) === true &&
            $this->parameters[static::PARAM_DATA] instanceof \Traversable
        ) {
            $stuffToDump[] = Traversable::class;
        }

        // Dumping debug methods.
        $stuffToDump[] = DebugMethods::class;

        return $stuffToDump;
    }

    /**
     * Dumping stuff is everywhere the same, only the callback class is changing.
     *
     * @var string $classname
     *   The name of the callback class we are using.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return string
     *   The generated html markup.
     */
    protected function dumpStuff(string $classname): string
    {
        return $this->pool
            ->createClass($classname)
            ->setParameters($this->parameters)
            ->callMe();
    }
}
