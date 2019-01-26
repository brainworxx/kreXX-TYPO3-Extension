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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;

/**
 * Analysis of public properties.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses mixed data
 *   The class we are currently analysing.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class PublicProperties extends AbstractObjectAnalysis
{
    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties';

    /**
     * Dump all public properties.
     *
     * @throws \ReflectionException
     *
     * @return string
     *   The generated HTML markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        $data = $ref->getData();

        $refProps = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        $publicProps = array();

        // Adding undeclared public properties to the dump.
        // Those are properties which are not visible with
        // ReflectionProperty::IS_PUBLIC
        // but are in get_object_vars
        //
        // 1. Make a list of all properties
        // 2. Remove those that are listed in
        // ReflectionProperty::IS_PUBLIC
        //
        // What is left are those special properties that were dynamically
        // set during runtime, but were not declared in the class.
        foreach ($refProps as $refProp) {
            $publicProps[$refProp->name] = true;
        }

        // For every not-declared property, we add a another reflection.
        // Those are simply added during runtime
        foreach (array_keys(array_diff_key(get_object_vars($data), $publicProps)) as $key) {
            $refProps[] = new UndeclaredProperty($ref, $key);
        }

        if (empty($refProps) === true) {
            return $output;
        }

        usort($refProps, array($this, 'reflectionSorting'));
        // Adding a HR to reflect that the following stuff are not public
        // properties anymore.
        return $output .
            $this->getReflectionPropertiesData($refProps, $ref) .
            $this->pool->render->renderSingeChildHr();
    }
}
