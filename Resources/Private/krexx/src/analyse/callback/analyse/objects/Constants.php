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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

/**
 * Class Constants analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Constants extends AbstractObjectAnalysis
{
    /**
     * Dumps the constants of a class,
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        // This is actually an array, we ara analysing. But We do not want to render
        // an array, so we need to process it like the return from an iterator.
        /** @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];
        $refConst = $ref->getConstants();

        if (empty($refConst) === true) {
            // Nothing to see here, return an empty string.
            return '';
        }

        // We've got some values, we will dump them.
        $classname = '\\' . $ref->getName();
        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName('Constants')
                ->setType('class internals')
                ->setIsMetaConstants(true)
                ->addParameter('data', $refConst)
                ->addParameter('classname', $classname)
                ->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConstants')
                )
        );
    }
}
