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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Service\Misc\File;

/**
 * Class properties analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array data
 *   Array of \reflectionProperties.
 * @uses ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 * @uses object orgObject
 *   The original object we are analysing
 */
class ThroughProperties extends AbstractCallback
{
    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties';

    /**
     * The file service, used to read and write files.
     *
     * @var File
     */
    protected $fileService;

    /**
     * A list with the default properties from this object.
     *
     * @var array
     */
    protected $defaultProperties = array();

    /**
     * The object, cast into an array.
     *
     * @var array
     */
    protected $objectArray = array();

    /**
     * Renders the properties of a class.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        // I need to preprocess them, since I do not want to render a
        // reflection property.
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        foreach ($this->parameters[static::PARAM_DATA] as $refProperty) {
            // Check memory and runtime.
            if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
                return '';
            }

            // Stitch together our additional info about the data:
            // public access, protected access, private access and info if it was
            // inherited from somewhere.
            $additional = $this->getAdditionalData($refProperty, $ref);

            // Every other additional string requires a special connector,
            // so we do this here.
            $connectorType = Connectors::NORMAL_PROPERTY;
            /** @var \ReflectionProperty $refProperty */
            $propName = $refProperty->getName();

            // Static properties are very special.
            if ($refProperty->isStatic() === true) {
                $connectorType = Connectors::STATIC_PROPERTY;
                // There is always a $ in front of a static property.
                $propName = '$' . $propName;
            }

            $value = $ref->retrieveValue($refProperty);

            if (isset($refProperty->isUnset) === true) {
                $additional .= 'unset ';
            }

            if (isset($refProperty->isUndeclared) === true) {
                // The property 'isUndeclared' is not a part of the reflectionProperty.
                // @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
                $additional .= 'dynamic property ';

                // Check for very special chars in there.
                // AFAIK this is only possible for dynamically declared properties
                // which can never be static.
                if ($this->isPropertyNameNormal($propName) === false) {
                    $connectorType = Connectors::SPECIAL_CHARS_PROP;
                }

                // There is no comment ora declaration place for a dynamic property.
                $comment = '';
                $declarationPlace = '';
            } else {
                // Since we are dealing with a declared Property here, we can
                // get the comment and the declaration place.
                $comment = $this->pool
                    ->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Properties')
                    ->getComment($refProperty);

                $declarationPlace = $this->pool->fileService->filterFilePath(
                    $refProperty->getDeclaringClass()->getFileName()
                );
            }

            // Stitch together our model
            $output .= $this->pool->routing->analysisHub(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($value)
                        ->setName($this->pool->encodingService->encodeString($propName))
                        ->addToJson('Comment', $comment)
                        ->addToJson('Declared in', $declarationPlace)
                        ->setAdditional($additional)
                        ->setConnectorType($connectorType)
                )
            );
        }

        return $output;
    }

    /**
     * Adding declaration keywords to our data in the additional field.
     *
     * @param \ReflectionProperty $refProperty
     *   A reflection of the property we ara analysing.
     * @param \ReflectionClass $ref
     *   A reflection of the class we are analysing.
     *
     * @return string
     */
    protected function getAdditionalData(\ReflectionProperty $refProperty, \ReflectionClass $ref)
    {
        // Now that we have the key and the value, we can analyse it.
        // Stitch together our additional info about the data:
        // public access, protected access, private access, static declaration.
        $additional = '';
        if ($refProperty->isProtected() === true) {
            $additional .= 'protected ';
        } elseif ($refProperty->isPublic() === true) {
            $additional .= 'public ';
        } elseif ($refProperty->isPrivate() === true) {
            $additional .= 'private ';
        }

        // Test if the property is inherited or not by testing the
        // declaring class
        if ($refProperty->getDeclaringClass()->getName() !== $ref->getName()) {
            // This one got inherited fom a lower level.
            $additional .= 'inherited ';
        }

        // Add the info, if this is static.
        if ($refProperty->isStatic() === true) {
            $additional .= 'static ';
        }

        return $additional;
    }
}