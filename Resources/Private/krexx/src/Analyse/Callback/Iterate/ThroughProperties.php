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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Comment\Properties;
use Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Class properties' analysis methods.
 *
 * @uses array data
 *   Array of \ReflectionProperties.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class ThroughProperties extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * @var PropertyDeclaration
     */
    protected $propertyDeclaration;

    /**
     * Renders the properties of a class.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        // I need to preprocess them, since I do not want to render a
        // reflection property.
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        $this->propertyDeclaration = $this->pool->createClass(PropertyDeclaration::class);

        foreach ($this->parameters[static::PARAM_DATA] as $refProperty) {
            // Check memory and runtime.
            if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
                return '';
            }

            $output .= $this->pool->routing->analysisHub(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->prepareModel($ref->retrieveValue($refProperty), $refProperty)
                )
            );
        }

        return $output;
    }

    /**
     * Prepare the model.
     *
     * @param mixed $value
     *   The retrieved value
     * @param \ReflectionProperty $refProperty
     *   The reflection of the property we are analysing.
     *
     * @return \Brainworxx\Krexx\Analyse\Model
     *   The prepared model.
     */
    protected function prepareModel($value, ReflectionProperty $refProperty): Model
    {
        $messages = $this->pool->messages;

        return $this->pool->createClass(Model::class)
            ->setData($value)
            ->setName($this->retrievePropertyName($refProperty))
            ->addToJson(
                $messages->getHelp('metaComment'),
                $this->pool->createClass(Properties::class)->getComment($refProperty)
            )
            ->addToJson(
                $messages->getHelp('metaDeclaredIn'),
                $this->propertyDeclaration->retrieveDeclaration($refProperty)
            )
            ->addToJson(
                $messages->getHelp('metaDefaultValue'),
                $this->retrieveDefaultValue($refProperty)
            )
            ->addToJson(
                $messages->getHelp('metaTypedValue'),
                $this->propertyDeclaration->retrieveNamedPropertyType($refProperty)
            )
            ->setAdditional(
                $this->getAdditionalData($refProperty, $this->parameters[static::PARAM_REF])
            )
            ->setConnectorType($this->retrieveConnector($refProperty))
            ->setCodeGenType($refProperty->isPublic() ? static::CODEGEN_TYPE_PUBLIC : '');
    }

    /**
     * Retrieve the default value, if possible.
     *
     * @param ReflectionProperty $property
     *
     * @return string
     */
    protected function retrieveDefaultValue(ReflectionProperty $property): string
    {
        $default = null;

        try {
            // The 8.0 way of getting the default value.
            // There is also a PHP 8.0 bug that may cause an
            // "Internal error: Failed to retrieve the reflection object"
            // That is not even a Reflection exception, it's an "Error".
            $default = $property->getDefaultValue();
        } catch (Throwable $exception) {
            // Fallback to the 7.x way.
            // The values of static properties are stored in the default
            // properties of the class reflection.
            // And we do not want these here.
            // @deprecated
            //   Will be removed as soon als we drop php 8.0 support.
            if (!$property->isStatic()) {
                // We also need to get the class that actually declared this
                // value. The default values can only be found in there.
                $defaultProperties = $property->getDeclaringClass()->getDefaultProperties();
                $default = $defaultProperties[$property->getName()] ?? null;
            }
        }

        return $default === null ? '' : $this->formatDefaultValue($default);
    }

    /**
     * Format the default value into something readable
     *
     * @param string|int|float|array $default
     * @return string
     */
    protected function formatDefaultValue($default): string
    {
        if (is_int($default) || is_float($default)) {
            // We do not need to escape an integer or a float,
            return (string)$default;
        }

        $result = '';
        if (is_string($default)) {
            $result = '\'' . $default . '\'';
        } elseif (is_array($default)) {
            $result = var_export($default, true);
        }

        return nl2br($this->pool->encodingService->encodeString($result));
    }

    /**
     * Retrieve the connector type, depending on the property properties
     *
     * @param \ReflectionProperty $refProperty
     *   Reflection of the property we are analysing.
     *
     * @return string
     *   The connector-type.
     */
    protected function retrieveConnector(ReflectionProperty $refProperty): string
    {
        $connectorType = static::CONNECTOR_NORMAL_PROPERTY;

        if ($refProperty->isStatic()) {
            $connectorType = static::CONNECTOR_STATIC_PROPERTY;
        } elseif (
            !empty($refProperty->isUndeclared) &&
            !$this->isPropertyNameNormal($refProperty->getName())
        ) {
            // This one was undeclared and does not follow the standard naming
            // conventions of PHP. Maybe something for a rest service?
            $connectorType = static::CONNECTOR_SPECIAL_CHARS_PROP;
        }

        return $connectorType;
    }

    /**
     * Retrieval of the property name, and processing it.
     *
     * @param \ReflectionProperty $refProperty
     *   Reflection of the property we are analysing.
     *
     * @return string
     *   The processed property name.
     */
    protected function retrievePropertyName(ReflectionProperty $refProperty): string
    {
        $propName = $refProperty->getName();
        // Static properties are very special.
        if ($refProperty->isStatic()) {
            // There is always a $ in front of a static property.
            $propName = '$' . $propName;
        } elseif (
            !empty($refProperty->isUndeclared) &&
            !$this->isPropertyNameNormal($refProperty->getName())
        ) {
            // There can be anything in there. We must take special preparations
            // for the code generation.
            $propName = $this->pool->encodingService->encodeString($propName);
        }

        return $propName;
    }

    /**
     * Adding declaration keywords to our data in the additional field.
     *
     * @param ReflectionProperty $refProperty
     *   A reflection of the property we ara analysing.
     * @param ReflectionClass $ref
     *   A reflection of the class we are analysing.
     *
     * @return string
     */
    protected function getAdditionalData(ReflectionProperty $refProperty, ReflectionClass $ref): string
    {
        $messages = $this->pool->messages;
        $additional = $messages->getHelp('public') . ' ';

        if (!empty($refProperty->isUndeclared)) {
            // The property 'isUndeclared' is not a part of the reflectionProperty.
            // @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
            // A dynamically declared property is always public, and nothing else.
            return $additional . $messages->getHelp('dynamic') . ' ';
        }

        // Now that we have the key and the value, we can analyse it.
        // Stitch together our additional info about the data:
        // public access, protected access, private access, static declaration.
        if ($refProperty->isProtected()) {
            $additional = $messages->getHelp('protected') . ' ';
        } elseif ($refProperty->isPrivate()) {
            $additional = $messages->getHelp('private') . ' ';
        }

        // Retrieve the value status of the property.
        $additional .= $this->retrieveValueStatus($refProperty, $ref);

        // Test if the property is inherited or not by testing the
        // declaring class
        if ($refProperty->getDeclaringClass()->getName() !== $ref->getName()) {
            // This one got inherited fom a lower level.
            $additional .= $messages->getHelp('inherited') . ' ';
        }

        // Add the info, if this is static.
        if ($refProperty->isStatic()) {
            $additional .= $messages->getHelp('static') . ' ';
        }

        return $additional;
    }

    /**
     * Retrieve the value status of a property:
     *   - readonly
     *   - uninitialized (not yet with a value)
     *   - unset (not with a value anymore)
     *
     * @param \ReflectionProperty $refProperty
     *   The reflection of the property we are analysing.
     *
     * @return string
     *   The human-readable result string.
     */
    protected function retrieveValueStatus(ReflectionProperty $refProperty, ReflectionClass $ref): string
    {
        $additional = '';
        $messages = $this->pool->messages;

        // There are readonly properties since PHP 8.1 available.
        // In a rather buggy state. When the property is not readonly, this may
        // trigger an
        // "Error : Internal error: Failed to retrieve the reflection object".
        try {
            if ($refProperty->isReadOnly()) {
                $additional .= $messages->getHelp('readonly') . ' ';
            }
        } catch (Throwable $exception) {
            // Do nothing.
            // We ignore this one.
        }

        if (!$ref->isPropertyUnset($refProperty)) {
            return $additional;
        }

        if (method_exists($refProperty, 'hasType') && $refProperty->hasType()) {
            // Typed properties where introduced in 7.4.
            // This one was either unset, or never received a value in the
            // first place. Either way, it's status is uninitialized.
            return $additional . $messages->getHelp('uninitialized') . ' ';
        }

        // This one was unset during runtime.
        // We need to tell the dev. Accessing an unset property may trigger
        // a warning.
        return $additional . $messages->getHelp('unset') . ' ';
    }

    /**
     * Retrieve the declaration place of a property.
     *
     * @param \ReflectionProperty $refProperty
     *   A reflection of the property we are analysing.
     *
     * @deprecated since 5.0.0
     *   Will be removed. Use PropertyDeclaration instead.
     * @codeCoverageIgnore
     *   We do not test deprecated methods.
     *
     * @return string
     *   Human-readable string, where the property was declared.
     */
    protected function retrieveDeclarationPlace(ReflectionProperty $refProperty): string
    {
        return $this->pool->createClass(PropertyDeclaration::class)
            ->retrieveDeclaration($refProperty);
    }

    /**
     * Check for special chars in properties.
     *
     * AFAIK this is only possible for dynamically declared properties
     * or some magical stuff from __get()
     *
     * @see https://stackoverflow.com/questions/29019484/validate-a-php-variable
     * @author AbraCadaver
     *
     * @param string|int $propName
     *   The property name we want to check.
     * @return bool
     *   Whether we have a special char in there, or not.
     */
    public function isPropertyNameNormal($propName): bool
    {
        static $cache = [];

        if (isset($cache[$propName])) {
            return $cache[$propName];
        }

        // The first regex detects all allowed characters.
        // For some reason, they also allow BOM characters.
        return $cache[$propName] = (bool) preg_match(
            "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/",
            (string)$propName
        ) && !(bool) preg_match("/\xEF\xBB\xBF/", $propName);
    }
}
