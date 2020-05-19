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
use Exception;
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
    public function callMe()
    {
        $output = $this->pool->render->renderSingeChildHr() . $this->dispatchStartEvent();
        $data = $this->parameters[static::PARAM_DATA];
        $ref = $this->parameters[static::PARAM_REF] = new ReflectionClass($data);

        // Dumping public properties.
        $output .= $this->dumpStuff(PublicProperties::class);

        if ($ref->getName() === \__PHP_Incomplete_Class::class) {
            // Early return for broken objects.
            return $output . $this->pool->render->renderSingeChildHr();
        }

        // Dumping getter methods.
        // We will not dump the getters for internal classes, though.
        if ($this->pool->config->getSetting(Fallback::SETTING_ANALYSE_GETTER) === true &&
            $ref->isUserDefined() === true
        ) {
            $output .= $this->dumpStuff(Getter::class);
        }

        $output .= $this->dumpStuff(Meta::class);

        // Analysing error objects.
        if (is_a($data, Throwable::class) ||
            is_a($data, Exception::class)
        ) {
            $output .= $this->dumpStuff(ErrorObject::class);
        }

        // Dumping protected properties.
        if ($this->pool->config->getSetting(Fallback::SETTING_ANALYSE_PROTECTED) === true ||
            $this->pool->scope->isInScope() === true
        ) {
            $output .= $this->dumpStuff(ProtectedProperties::class);
        }

        // Dumping private properties.
        if ($this->pool->config->getSetting(Fallback::SETTING_ANALYSE_PRIVATE) === true ||
            $this->pool->scope->isInScope() === true
        ) {
            $output .= $this->dumpStuff(PrivateProperties::class);
        }

        // Dumping class constants.
        $output .= $this->dumpStuff(Constants::class);

        // Dumping all methods.
        $output .= $this->dumpStuff(Methods::class);

        // Dumping traversable data.
        if ($this->pool->config->getSetting(Fallback::SETTING_ANALYSE_TRAVERSABLE) === true &&
            $data instanceof \Traversable
        ) {
            $output .= $this->dumpStuff(Traversable::class);
        }

        // Dumping all configured debug functions.
        $output .=  $this->dumpStuff(DebugMethods::class);


        // Adding a HR for a better readability.
        return $output . $this->pool->render->renderSingeChildHr();
    }

    /**
     * Dumping stuff is everywhere the same, only the callback class is changing.
     *
     * @var string $classname
     *   The name of the callback class we are using.
     *
     * @return string
     *   The generated html markup.
     */
    protected function dumpStuff($classname)
    {
        return $this->pool
            ->createClass($classname)
            ->setParameters($this->parameters)
            ->callMe();
    }
}
