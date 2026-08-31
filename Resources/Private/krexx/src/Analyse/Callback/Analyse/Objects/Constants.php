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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Class Constants analysis.
 *
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Constants extends AbstractObjectAnalysis implements CodegenConstInterface
{
    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Dumps the constants of a class,
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        /** @var \ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        // Retrieve the constants that are accessible in the scope of the class.
        // The problem is, that the private const may or may not be available
        // inside the higher class structure, because these parts do not inherit
        // them.
        $listOfConstants = $ref->getConstants();
        if (empty($listOfConstants)) {
            // Nothing to see here, return an empty string.
            return '';
        }

        // We've got some values, we will dump them.
        return $output . $this->pool->render->renderExpandableChild(
            model: $this->dispatchEventWithModel(
                name: static::EVENT_MARKER_ANALYSES_END,
                model: $this->pool->createClass(classname: Model::class)
                    ->setName(name: $this->pool->messages->getHelp(key: 'metaConstants'))
                    ->setType(type: $this->pool->messages->getHelp(key: 'classInternals'))
                    ->setCodeGenType(codeGenType: static::CODEGEN_TYPE_META_CONSTANTS)
                    ->addParameter(name: static::PARAM_DATA, value: $listOfConstants)
                    ->addParameter(name: static::PARAM_REF, value: $ref)
                    ->injectCallback(
                        object: $this->pool->createClass(classname: ThroughConstants::class)
                    )
            )
        );
    }
}
