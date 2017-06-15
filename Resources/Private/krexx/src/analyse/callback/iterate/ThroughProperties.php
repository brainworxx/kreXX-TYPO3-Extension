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
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 * @uses object orgObject
 *   The original object we are analysing
 */
class ThroughProperties extends AbstractCallback
{

    /**
     * The file service, used to read and write files.
     *
     * @var File
     */
    protected $fileService;

    /**
     * Renders the properties of a class.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        // I need to preprocess them, since I do not want to render a
        // reflection property.
        /* @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];
        $output = '';
        $default = $ref->getDefaultProperties();

        foreach ($this->parameters['data'] as $refProperty) {
            // Check memory and runtime.
            if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
                return '';
            }

            /* @var \ReflectionProperty $refProperty */
            $refProperty->setAccessible(true);

            // Getting our values from the reflection.
            $value = $refProperty->getValue($this->parameters['orgObject']);
            $propName = $refProperty->name;
            if (is_null($value) && $refProperty->isDefault() && isset($default[$propName])) {
                // We might want to look at the default value.
                $value = $default[$propName];
            }

            // Now that we have the key and the value, we can analyse it.
            // Stitch together our additional info about the data:
            // public, protected, private, static.
            $additional = '';
            if ($refProperty->isProtected()) {
                $additional .= 'protected ';
            } elseif ($refProperty->isPublic()) {
                $additional .= 'public ';
            } elseif ($refProperty->isPrivate()) {
                $additional .= 'private ';
            }

            // Test if the property is inherited or not by testing the
            // declaring class
            if ($refProperty->getDeclaringClass()->getName() !== $ref->getName()) {
                // This one got inherited fom a lower level.
                $additional .= 'inherited ';
            }

            $comment = '';
            $declarationPlace = '';

            $connectorType = Connectors::NORMAL_PROPERTY;
            if ($refProperty->isStatic()) {
                $additional .= 'static ';
                $connectorType = Connectors::STATIC_PROPERTY;
                // There is always a $ in front of a static property.
                $propName = '$' . $propName;
            } elseif (isset($refProperty->isUndeclared)) {
                // The property 'isUndeclared' is not a part of the reflectionProperty.
                // @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
                $additional .= 'dynamic property ';

                // Check for very special chars in there.
                // FAIK this is only possible for dynamically declared properties
                // which can never be static.
                if (!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $propName)) {
                    // @see https://stackoverflow.com/questions/29019484/validate-a-php-variable
                    // @author AbraCadaver
                    $connectorType = Connectors::SPECIAL_CHARS_PROP;
                }
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
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($value)
                    ->setName($this->pool->encodingService->encodeString($propName))
                    ->addToJson('Comment', $comment)
                    ->addToJson('Declared in', $declarationPlace)
                    ->setAdditional($additional)
                    ->setConnectorType($connectorType)
            );
        }

        return $output;
    }
}
