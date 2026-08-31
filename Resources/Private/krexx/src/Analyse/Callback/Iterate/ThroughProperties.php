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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Comment\Attributes;
use Brainworxx\Krexx\Analyse\Comment\Comment;
use Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionProperty;
use Throwable;
use UnitEnum;

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
    protected PropertyDeclaration $propertyDeclaration;

    /**
     * @var Properties
     */
    protected Comment $propertyComment;

    /**
     * @var Attributes
     */
    protected Attributes $attributes;

    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

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
        /** @var ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        $this->propertyDeclaration = $this->pool->createClass(classname: PropertyDeclaration::class);
        $this->propertyComment = $this->pool->createClass(classname: Comment::class);
        $this->attributes = $this->pool->createClass(classname: Attributes::class);

        foreach ($this->parameters[static::PARAM_DATA] as $refProperty) {
            // Check memory and runtime.
            if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
                return '';
            }

            $output .= $this->pool->routing->analysisHub(
                model: $this->dispatchEventWithModel(
                    name: __FUNCTION__ . static::EVENT_MARKER_END,
                    model: $this->prepareModel(
                        value: $ref->retrieveValue(refProperty: $refProperty),
                        refProperty: $refProperty
                    )
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
     * @return Model
     *   The prepared model.
     */
    protected function prepareModel(mixed $value, ReflectionProperty $refProperty): Model
    {
        $messages = $this->pool->messages;
        return $this->pool->createClass(classname: Model::class)
            ->setData(data: $value)
            ->setName(name: $this->retrievePropertyName(refProperty: $refProperty))
            ->addToJson(
                key: $messages->getHelp(key: 'metaComment'),
                value: nl2br($this->propertyComment->getComment(reflection: $refProperty))
            )->addToJson(
                key: $messages->getHelp(key: 'metaAttributes'),
                // Meh, the addToJson method does not support real new lines.
                value: nl2br(string: $this->attributes->getAttributes(reflection: $refProperty))
            )->addToJson(
                key: $messages->getHelp(key: 'metaDeclaredIn'),
                value: $this->propertyDeclaration->retrieveDeclaration(reflection: $refProperty)
            )->addToJson(
                key: $messages->getHelp(key: 'metaDefaultValue'),
                value: $this->retrieveDefaultValue(property: $refProperty)
            )->addToJson(
                key: $messages->getHelp(key: 'metaTypedValue'),
                value: $this->propertyDeclaration->retrieveNamedPropertyType($refProperty)
            )
            ->setAdditional(additional: $this->getAdditionalData(
                refProperty: $refProperty,
                ref: $this->parameters[static::PARAM_REF]
            ))->setConnectorType(type: $this->retrieveConnector(refProperty: $refProperty))
            ->setCodeGenType(codeGenType: $refProperty->isPublic() ? static::CODEGEN_TYPE_PUBLIC : '');
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
            if ($property->hasDefaultValue()) {
                $default = $property->getDefaultValue();
            }
        } catch (Throwable) {
            // The values of static properties are stored in the default
            // properties of the class reflection.
            // And we do not want these here.
            if (!$property->isStatic()) {
                // We also need to get the class that actually declared this
                // value. The default values can only be found in there.
                $defaultProperties = $property->getDeclaringClass()->getDefaultProperties();
                $default = $defaultProperties[$property->getName()] ?? null;
            }
        }

        return $default === null ? '' : $this->formatDefaultValue(default: $default);
    }

    /**
     * Format the default value into something readable
     *
     * @param bool|string|int|float|array|UnitEnum $default
     * @return string
     */
    protected function formatDefaultValue(bool|string|int|float|array|UnitEnum $default): string
    {
        if (is_int(value: $default) || is_float(value: $default)) {
            // We do not need to escape an integer or a float,
            return (string)$default;
        }

        if (is_bool(value: $default)) {
            return $default ? 'TRUE' : 'FALSE';
        }

        $result = '';
        if (is_string(value: $default)) {
            $result = '\'' . $default . '\'';
        } elseif (is_array(value: $default) || $default instanceof UnitEnum) {
            $result = var_export(value: $default, return: true);
        }

        return nl2br(string: $this->pool->encodingService->encodeString(data: $result));
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
            !$this->isPropertyNameNormal(propName: $refProperty->getName())
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
            !$this->isPropertyNameNormal(propName: $refProperty->getName())
        ) {
            // There can be anything in there. We must take special preparations
            // for the code generation.
            $propName = $this->pool->encodingService->encodeString(data: $propName);
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
        $additional = $messages->getHelp(key: 'public') . ' ';

        if (!empty($refProperty->isUndeclared)) {
            // The property 'isUndeclared' is not a part of the reflectionProperty.
            // @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
            // A dynamically declared property is always public, and nothing else.
            return $additional . $messages->getHelp(key: 'dynamic') . ' ';
        }

        // Now that we have the key and the value, we can analyse it.
        // Stitch together our additional info about the data:
        // public access, protected access, private access, static declaration.
        if ($refProperty->isProtected()) {
            $additional = $messages->getHelp(key: 'protected') . ' ';
        } elseif ($refProperty->isPrivate()) {
            $additional = $messages->getHelp(key: 'private') . ' ';
        }

        // Retrieve the value status of the property.
        $additional .= $this->retrieveValueStatus(refProperty: $refProperty, ref: $ref);

        // Test if the property is inherited or not by testing the
        // declaring class
        if ($refProperty->getDeclaringClass()->getName() !== $ref->getName()) {
            // This one got inherited fom a lower level.
            $additional .= $messages->getHelp(key: 'inherited') . ' ';
        }

        // Add the info, if this is static.
        if ($refProperty->isStatic()) {
            $additional .= $messages->getHelp(key: 'static') . ' ';
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

        try {
            if ($refProperty->isReadOnly()) {
                $additional .= $messages->getHelp(key: 'readonly') . ' ';
            }
        } catch (Throwable) {
            // Do nothing. We ignore this one.
        }

        if (!$ref->isPropertyUnset(reflectionProperty: $refProperty)) {
            return $additional;
        }

        if ($refProperty->hasType()) {
            // Typed properties where introduced in 7.4.
            // This one was either unset, or never received a value in the
            // first place. Either way, it's status is uninitialized.
            return $additional . $messages->getHelp(key: 'uninitialized') . ' ';
        }

        // This one was unset during runtime.
        // We need to tell the dev. Accessing an unset property may trigger
        // a warning.
        return $additional . $messages->getHelp(key: 'unset') . ' ';
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
    public function isPropertyNameNormal(string|int $propName): bool
    {
        static $cache = [];

        // The first regex detects all allowed characters.
        // For some reason, they also allow BOM characters.
        return $cache[$propName] ?? $cache[$propName] = (bool) preg_match(
            pattern: "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/",
            subject: (string)$propName
        ) && !(bool) preg_match(pattern: "/\xEF\xBB\xBF/", subject: (string) $propName);
    }
}
