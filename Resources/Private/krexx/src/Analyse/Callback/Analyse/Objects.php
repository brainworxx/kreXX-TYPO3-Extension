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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use __PHP_Incomplete_Class;
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
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Logging\Model as LogModel;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use stdClass;
use Throwable;

/**
 * Object analysis methods.
 *
 * @uses object data
 *   The class we are analysing.
 * @uses string name
 *   The key of the class from the object/array holding this one.
 */
class Objects extends AbstractCallback implements CallbackConstInterface, ConfigConstInterface
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
        // Adding an HR for a better readability.
        return $output . $this->pool->render->renderSingeChildHr();
    }

    /**
     * Generate a list of classes that will analyse the object.
     *
     * @throws \ReflectionException
     *
     * @return string[]
     *   The list with class names for the analysis.
     */
    protected function generateDumperList(): array
    {
        $data = $this->parameters[static::PARAM_DATA];
        $ref = $this->parameters[static::PARAM_REF] = new ReflectionClass($data);
        $config = $this->pool->config;
        $stuffToDump = [PublicProperties::class];

        if (in_array($ref->getName(), [stdClass::class, __PHP_Incomplete_Class::class], true)) {
            // We ignore everything else for these two types.
            return $stuffToDump;
        }

        // Analysing error objects.
        if (
            $data instanceof Throwable
            || $data instanceof LogModel
        ) {
            $stuffToDump[] = ErrorObject::class;
        }

        // Dumping all the property related stuff.
        $this->addPropertyDumper($stuffToDump);

        // Dumping class meta information.
        $stuffToDump[] = Meta::class;

        // Dumping class constants.
        $stuffToDump[] = Constants::class;

        // Dumping all methods.
        $stuffToDump[] = Methods::class;

        // Dumping traversable data.
        if (
            $config->getSetting(static::SETTING_ANALYSE_TRAVERSABLE) === true
            && $data instanceof \Traversable
        ) {
            $stuffToDump[] = Traversable::class;
        }

        // Dumping debug methods.
        $stuffToDump[] = DebugMethods::class;

        return $stuffToDump;
    }

    /**
     * Adding property and the getter-analysis to the list.
     *
     * @param string[] $stuffToDump
     *   The stuff to dump, so far.
     */
    protected function addPropertyDumper(array &$stuffToDump): void
    {
        $isInScope = $this->pool->scope->isInScope();
        $config = $this->pool->config;

        // Dumping getter methods before the protected and private,
        // in case we are not in scope.
        if (!$isInScope && $config->getSetting(static::SETTING_ANALYSE_GETTER)) {
            $stuffToDump[] = Getter::class;
        }

        // Dumping protected properties.
        if ($isInScope || $config->getSetting(static::SETTING_ANALYSE_PROTECTED)) {
            $stuffToDump[] = ProtectedProperties::class;
        }

        // Dumping private properties.
        if ($isInScope || $config->getSetting(static::SETTING_ANALYSE_PRIVATE)) {
            $stuffToDump[] = PrivateProperties::class;
        }

        // Dumping getter methods before the protected and private,
        // in case we are in scope.
        if ($isInScope && $config->getSetting(static::SETTING_ANALYSE_GETTER)) {
            $stuffToDump[] = Getter::class;
        }
    }
}
