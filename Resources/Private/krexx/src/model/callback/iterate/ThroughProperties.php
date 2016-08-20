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

namespace Brainworxx\Krexx\Model\Callback\Iterate;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Model\Callback\AbstractCallback;

/**
 * Class properties analysis methods.
 *
 * @package Brainworxx\Krexx\Model\Callback\Iterate
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
            /* @var \ReflectionProperty $refProperty */
            $refProperty->setAccessible(true);

            // Getting our values from the reflection.
            $value = $refProperty->getValue($this->parameters['orgObject']);
            $propName = $refProperty->name;
            if (is_null($value) && $refProperty->isDefault()) {
                // We might want to look at the default value.
                $value = $default[$propName];
            }

            // Check memory and runtime.
            if (!$this->storage->emergencyHandler->checkEmergencyBreak()) {
                return '';
            }

            // Recursion tests are done in the analyseObject and
            // iterateThrough (for arrays).
            // We will not check them here.
            // Now that we have the key and the value, we can analyse it.
            // Stitch together our additional info about the data:
            // public, protected, private, static.
            $additional = '';
            $connector1 = '->';
            if ($refProperty->isPublic()) {
                $additional .= 'public ';
            }
            if ($refProperty->isPrivate()) {
                $additional .= 'private ';
            }
            if ($refProperty->isProtected()) {
                $additional .= 'protected ';
            }
            if (is_a($refProperty, '\Brainworxx\Krexx\Analysis\Flection')) {
                /* @var \Brainworxx\Krexx\Analysis\Flection $refProperty */
                $additional .= $refProperty->getWhatAmI() . ' ';
            }
            if ($refProperty->isStatic()) {
                $additional .= 'static ';
                $connector1 = '::';
                // There is always a $ in front of a static property.
                $propName = '$' . $propName;
            }

            // Stitch together our model
            $model = new Simple($this->storage);
            $model->setData($value)
                ->setName($propName)
                ->setAdditional($additional)
                ->setConnector1($connector1);

            $output .= $this->storage->routing->analysisHub($model);
        }

        return $output;
    }
}
