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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Service\Reflection;

/**
 * Added a better possibility to retrieve the object values.
 *
 * @package Service\Reflection
 */
class ReflectionClass extends \ReflectionClass
{
    /**
     * The object, cast into an array.
     *
     * @var array
     */
    protected $objectArray = array();

    /**
     * The object we are currently analysing.
     *
     * @var mixed
     */
    protected $data;

    /**
     * ReflectionClass constructor.
     *
     * @param $data
     *   The class we are currently analysing.
     * @throws \ReflectionException
     */
    public function __construct($data)
    {
        // Retrieve the class variables.
        $this->objectArray = (array) $data;
        // Remember the current object.
        $this->data = $data;

        parent::__construct($data);
    }

    /**
     * Retrieve the value from the object, if possible.
     *
     * @param \ReflectionProperty $refProperty
     *   The reflection of the property we are analysing.
     *
     * @return mixed;
     *   The retrieved value.
     */
    public function retrieveValue(\ReflectionProperty $refProperty)
    {
        $propName = $refProperty->name;

        if (array_key_exists("\0*\0" . $propName, $this->objectArray)) {
            // Protected or a private
            return $this->objectArray["\0*\0" . $propName];
        }

        if (array_key_exists($propName, $this->objectArray)) {
            // Must be a public. Those are rare.
            return $this->objectArray[$propName];
        }

        if ($refProperty->isStatic() === true) {
            // Static values are not inside the value array.
            $refProperty->setAccessible(true);
            return $refProperty->getValue($this->data);
        }

        // If we are facing multiple declarations, the declaring class name
        // is set in front of the key.
        $propName = "\0" . $refProperty->getDeclaringClass()->getName() . "\0" . $propName;
        if (array_key_exists($propName, $this->objectArray)) {
            // Found it!
            return $this->objectArray[$propName];
        }

        // We are still here, which means that we are not able to get the value
        // out of it. The only remaining possibility is, that this value was
        // unset during runtime.
        $refProperty->isUnset = true;
        return null;
    }

    /**
     * Get the instance, from which this reflection was created.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
