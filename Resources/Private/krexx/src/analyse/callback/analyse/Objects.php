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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

/**
 * Object analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analysis
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
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->pool->render->renderSingeChildHr();

        $data = $this->parameters['data'];
        $ref = $this->parameters['ref'] = new \ReflectionClass($data);

        // Dumping public properties.
        $output .= $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties')
            ->setParams($this->parameters)
            ->callMe();

        // Dumping getter methods.
        // We will not dump the getters for internal values, though.
        if ($this->pool->config->getSetting('analyseGetter') &&
            $ref->isUserDefined()
        ) {
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Getter')
                ->setParams($this->parameters)
                ->callMe();
        }

        // Dumping protected properties.
        if ($this->pool->config->getSetting('analyseProtected') ||
            $this->pool->scope->isInScope()
        ) {
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\ProtectedProperties')
                ->setParams($this->parameters)
                ->callMe();
        }

        // Dumping private properties.
        if ($this->pool->config->getSetting('analysePrivate') ||
            $this->pool->scope->isInScope()
        ) {
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PrivateProperties')
                ->setParams($this->parameters)
                ->callMe();
        }

        // Dumping class constants.
        if ($this->pool->config->getSetting('analyseConstants')) {
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Constants')
                ->setParams($this->parameters)
                ->callMe();
        }

        // Dumping all methods.
        $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods')
                ->setParams($this->parameters)
                ->callMe();

        // Dumping traversable data.
        if ($this->pool->config->getSetting('analyseTraversable') &&
            is_a($data, 'Traversable')
        ) {
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Traversable')
                ->setParams($this->parameters)
                ->callMe();
        }

        // Dumping all configured debug functions.
        // Adding a HR for a better readability.
        return $output . $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods')
                ->setParams($this->parameters)
                ->callMe() .
            $this->pool->render->renderSingeChildHr();
    }
}
