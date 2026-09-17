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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter as OriginalGetter;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;

class Getter extends OriginalGetter
{
    /**
     * Dump the possible result of all getter methods
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        // Get all public methods.
        $this->retrieveMethodList($ref);
        // The 'get' method indicated dynamic fluid getter.
        // If there are no other getter methods, we do not show this section.
        if (
            empty($this->normalGetter + $this->isGetter + $this->hasGetter)
            && !$ref->hasMethod(name: 'get')
        ) {
            // There are no getter methods in here.
            return '';
        }

        return $this->pool->createClass(classname: Model::class)
            ->injectCallback($this->pool->createClass(classname: ThroughGetter::class))
            ->setName(name: $this->pool->messages->getHelp('getter'))
            ->setHelpid(helpId: 'getterHelpInfo')
            ->setType(type: $this->pool->messages->getHelp('classInternals'))
            ->addParameter(name: static::PARAM_IS_GETTER, value: $this->isGetter)
            ->addParameter(name: static::PARAM_REF, value: $ref)
            ->addParameter(name: static::PARAM_NORMAL_GETTER, value: $this->normalGetter)
            ->addParameter(name: static::PARAM_HAS_GETTER, value: $this->hasGetter)
            ->renderMe() . $this->pool->render->renderSingeChildHr();
    }
}
