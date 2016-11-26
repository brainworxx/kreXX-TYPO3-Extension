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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Getter method analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array methodList
 *   The list of all methods we are analysing
 * @uses \ReflectionClass $ref
 *   A reflection class of the object we are analysing.
 */
class ThroughGetter extends AbstractCallback
{

    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        /** @var \reflectionClass $ref */
        $ref = $this->parameters['ref'];

        foreach ($this->parameters['methodList'] as $methodName) {
            $propertyName = substr($methodName, 3);
            // We may be facing different writing styles.
            // The property we want from getMyProperty() should be named
            // myProperty, but we can not rely on this.
            // We will check:
            // MyProperty
            // myProperty
            // myproperty
            // my_property

            if ($ref->hasProperty($propertyName)) {
                $refProp = $ref->getProperty($propertyName);
            }
            $realName = lcfirst($propertyName);
            if ($ref->hasProperty(lcfirst($realName))) {
                $refProp = $ref->getProperty($realName);
            }
            $realName = strtolower($propertyName);
            if ($ref->hasProperty(strtolower($realName))) {
                $refProp = $ref->getProperty($realName);
            }
            $realName = $this->convertToSnakeCase($propertyName);
            if ($ref->hasProperty($this->convertToSnakeCase($realName))) {
                $refProp = $ref->getProperty($realName);
            }

            if (empty($refProp)) {
                // Found nothing  :-(
                $value = null;
            } else {
                // We've got ourself a possible result!
                $refProp->setAccessible(true);
                $value = $refProp->getValue($this->parameters['data']);
            }

            // We need to decide if we are handling static getters.


            $model = new Model($this->storage);
            $model->setData($value)
                ->setName($methodName)
                ->setConnector2('()');

            if ($ref->getMethod($methodName)->isStatic()) {
                $model->setConnector1('::');
            } else {
                $model->setConnector1('->');
            }

            $output .= $this->storage->routing->analysisHub($model);
        }

        return $output;
    }

    /**
     * Converts a camel case string to snake case.
     *
     * @author Syone
     * @see https://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case/35719689#35719689
     *
     * @param string $string
     *   The string we want to transform into snake case
     *
     * @return string
     *   The de-camelized string.
     */
    protected function convertToSnakeCase($string)
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }
}