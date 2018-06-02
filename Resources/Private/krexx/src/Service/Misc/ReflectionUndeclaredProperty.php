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

namespace Brainworxx\Krexx\Service\Misc;

/**
 * The original \ReflectionProperty may throw an error when used with
 * dynamically declared properties
 *
 * For some reasons, the \ReflectionProperty may claims that this property is
 * not there. We may have run into an interference of private properties in a
 * deeper class, __isset() which tries to dynamically resolve this, and some
 * public dynamically declared property with the same name as the private
 * property.
 * The __isset is called, because there is a private property somewhere with the
 * same name, so we can not rely on it..
 * The reflection property then claims that this property does not exist. The
 * only thing I can think of how to fix this, is with a mockup class for the
 * ReflectionProperty.
 * So. Much. Fun.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class ReflectionUndeclaredProperty extends \ReflectionProperty
{
    /**
     * This one is always undeclared.
     *
     * @var bool
     */
    public $isUndeclared = true;

    /**
     * The class instance with the property.
     *
     * @var object
     */
    protected $object;

    /**
     * THe name of the property.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * A reflection of the class, wheere the property was declared.
     *
     * @var \ReflectionClass
     */
    protected $declaringClass;

    /**
     * ReflectionUndeclaredProperty constructor.
     *
     * @param $class
     *   The instrance of the class with the property.
     * @param $name
     *   The name of the property.
     *
     * @throws \ReflectionException
     */
    public function __construct($class, $name)
    {
        $this->object = $class;
        $this->declaringClass = new \ReflectionClass($class);
        $this->propertyName = $name;
    }

    /**
     * A dynamically declared property can naver be static.
     *
     * @return bool
     *   Always false.
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * Getter for the reflection of the class with the property.
     *
     * @return \ReflectionClass
     *   The refelction.
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * A dynamically declared property can never have a default value.
     *
     * @return bool
     *   Always false.
     */
    public function isDefault()
    {
        return false;
    }

    /**
     * A dynamically declared property can never be private.
     *
     * @return bool
     *   Always false.
     */
    public function isPrivate()
    {
        return false;
    }

    /**
     * A dynamically declared property can never be protected.
     *
     * @return bool
     *   Always false.
     */
    public function isProtected()
    {
        return false;
    }

    /**
     * A dynamically declared property is always public.
     *
     * @return bool
     *   Always true.
     */
    public function isPublic()
    {
        return true;
    }

    /**
     * Try to retrieve the value, nested within some error handling.
     *
     * @param null $object
     *   We ignore this one. It's only here to make the class compartible with
     *   the original \ReflectionProperty.
     * @return mixed
     *   The value, if we can get it.
     */
    public function getValue($object = null)
    {
        set_error_handler(
            function () {
                // Do nothing.
            }
        );
        try {
            $value = $this->object->$this->objectName;
        } catch (\Throwable $e) {
            $value = null;
        } catch (\Exception $e) {
            $value = null;
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();
        return $value;
    }

    /**
     * Getter for the property name.
     *
     * @return string
     *   The property name.
     */
    public function getName()
    {
        return $this->propertyName;
    }

    /**
     * We need to implement thsi one without the possibility of throwing an
     * error in order to be compartible with te original \ReflectionClass.
     *
     * @return string
     *   Always an empty string.
     */
    public function __toString()
    {
        return '';
    }
}