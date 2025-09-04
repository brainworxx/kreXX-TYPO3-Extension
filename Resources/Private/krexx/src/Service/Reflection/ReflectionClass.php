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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Reflection;

use ReflectionException;
use ReflectionProperty;
use Throwable;
use SplObjectStorage;
use Krexx;

/**
 * Added a better possibility to retrieve the object values.
 */
class ReflectionClass extends \ReflectionClass
{
    /**
     * The object, cast into an array.
     *
     * @var array
     */
    protected array $objectArray = [];

    /**
     * The object we are currently analysing.
     *
     * @var object|string
     */
    protected $data;

    /**
     * Storage for unset properties.
     *
     * @var \SplObjectStorage
     */
    protected SplObjectStorage $unsetPropertyStorage;

    /**
     * ReflectionClass constructor.
     *
     * @param object|string $data
     *   The class we are currently analysing.
     *
     * @throws \ReflectionException
     */
    public function __construct($data)
    {
        // Retrieve the class variables.
        $this->objectArray = (array) $data;
        // Remember the current object.
        $this->data = $data;
        // Init our unset object storage;
        $this->unsetPropertyStorage = new SplObjectStorage();

        parent::__construct($data);
    }

    /**
     * Retrieve the value from the object, if possible.
     *
     * @param \ReflectionProperty $refProperty
     *   The reflection of the property we are analysing.
     *
     * @return mixed
     *   The retrieved value.
     */
    public function retrieveValue(ReflectionProperty $refProperty)
    {
        $propName = $refProperty->getName();
        $lookup = [
            // Protected properties
            "\0*\0" . $propName,
            // Inherited properties
            "\0" . $refProperty->getDeclaringClass()->getName() . "\0" . $propName,
            // Public properties.
            $propName
        ];

        foreach ($lookup as $arrayKey) {
            if (array_key_exists($arrayKey, $this->objectArray)) {
                return $this->objectArray[$arrayKey];
            }
        }

        try {
            if ($refProperty->isStatic()) {
                // Static values are not inside the value array.
                $refProperty->setAccessible(true);
                return $refProperty->getValue($this->data);
            }
        } catch (Throwable $throwable) {
            // Do nothing.
            // We ignore this one.
        }

        return $this->retrieveEsotericValue($refProperty);
    }

    /**
     * Retriever the value by more esoteric means.
     *
     * And by this I mean taking care of two PHP bugs:
     *   - Properties with integer names
     *   - Hidden public properties of the ext-dom objects
     *   - Hidden protected properties of the \DateTime object
     *
     * @param \ReflectionProperty $refProperty
     *   The reflection of the property that we are accessing.
     *
     * @return mixed
     */
    protected function retrieveEsotericValue(ReflectionProperty $refProperty)
    {
        $propName = $refProperty->getName();
        if ($refProperty instanceof UndeclaredProperty && is_numeric($propName)) {
            // We are facing a numeric property name (yes, that is possible).
            // To be honest, this one of the most bizarre things I've encountered so
            // far. Depending on your PHP version, that value may not be accessible
            // via normal means from the array we have got here. And no, we are not
            // accessing the object directly.
            return array_values($this->objectArray)[
                array_search($propName, array_keys($this->objectArray))
            ] ?? null;
        }

        if ($refProperty instanceof HiddenProperty) {
            // We need to access the value directly.
            // But first we must make sure that the hosting cms does not do
            // something stupid. Accessing this value directly it probably
            // a bad idea, but the only way to get the value.
            set_error_handler(Krexx::$pool->retrieveErrorCallback());
            try {
                $result = $this->data->$propName;
                restore_error_handler();
                return $result;
            } catch (Throwable $exception) {
                // Do nothing.
                // Looks like somebody did not like me accessing it directly.
            }
            restore_error_handler();
        }

        $this->unsetPropertyStorage->attach($refProperty);
        return null;
    }

    /**
     * The original get_object_vars() is problematic, because it
     * may fire PropertyHooks, LazyProxies or LazyGhosts.
     *
     * @return mixed[]
     */
    public function getObjectVars(): array
    {
        return array_filter($this->objectArray, function ($key) {
            return strpos((string)$key, "\0") === false;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Was this property unset?
     *
     * The info is only available if you retrieve the value beforehand.
     *
     * @param \ReflectionProperty $reflectionProperty
     * @return bool
     */
    public function isPropertyUnset(ReflectionProperty $reflectionProperty): bool
    {
        return $this->unsetPropertyStorage->contains($reflectionProperty);
    }

    /**
     * Get the instance, from which this reflection was created.
     *
     * @return object
     */
    public function getData(): object
    {
        return $this->data;
    }

    /**
     * Retrieve the actually implemented interfaces.
     *
     * @return ReflectionClass[]
     *   Array with the interfaces.
     */
    public function getInterfaces(): array
    {
        // Compare the names with the ones from the parent.
        $parent = $this->getParentClass();
        $interfaceNames = $this->getInterfaceNames();
        if ($parent !== false) {
            $interfaceNames = array_diff($interfaceNames, $parent->getInterfaceNames());
        }
        if (empty($interfaceNames)) {
            return [];
        }

        // Get the instances.
        $result = [];
        foreach ($interfaceNames as $interfaceName) {
            try {
                $result[$interfaceName] = new ReflectionClass($interfaceName);
            } catch (ReflectionException $exception) {
                // Do nothing. We skip this one.
                // Not sure how this could happen.
            }
        }

        return $result;
    }

    /**
     * Wrapper around the getTraits, to make sure we get our ReflectionClass.
     *
     * @return array|\ReflectionClass[]
     */
    public function getTraits(): array
    {
        $traits = parent::getTraitNames();
        if (empty($traits)) {
            return [];
        }

        $result = [];
        foreach ($traits as $trait) {
            try {
                $result[$trait] = new ReflectionClass($trait);
            } catch (ReflectionException $exception) {
                // We skip this one.
            }
        }

        return $result;
    }

    /**
     * Wrapper around the getParentClass, to make sure we get our ReflectionClass.
     *
     * @return bool|\ReflectionClass
     */
    #[\ReturnTypeWillChange]
    public function getParentClass()
    {
        $result = false;
        $parent = parent::getParentClass();
        if (!empty($parent)) {
            try {
                $result = new ReflectionClass($parent->name);
            } catch (ReflectionException $e) {
                // Do nothing.
            }
        }

        return $result;
    }
}
