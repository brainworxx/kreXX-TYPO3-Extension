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
use Brainworxx\Krexx\Analyse\Model;
use ReflectionClassConstant;

/**
 * Constant analysis methods.
 *
 * @uses array data
 *   Array of constants values from the class we are analysing.
 * @uses \ReflectionClass ref
 *   Reflection of the class we are analysing.
 *
 * Deprecated:
 * @uses string classname
 *   The classname we are analysing, for code generation purpose.
 *   Deprecated since 4.0.0. Will be removed. Ask the class reflection instead.
 */
class ThroughConstants extends AbstractCallback implements CallbackConstInterface, CodegenConstInterface
{
    /**
     * Are we coming from a $this scope?
     *
     * @var bool
     */
    protected $isInScope = false;

    /**
     * Simply iterate though object constants.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        // Setting the prefix, depending on the scope.
        $this->isInScope = $this->pool->scope->isInScope();
        $prefix = $this->isInScope ? 'static' : '\\' . $ref->getName();

        // Dump them with visibility infos.
        foreach ($this->parameters[static::PARAM_DATA] as $constantName => $constantValue) {
            /** @var ReflectionClassConstant $reflectionConstant */
            $reflectionConstant = $this->parameters[static::PARAM_REF]->getReflectionConstant($constantName);
            if ($this->canDump($reflectionConstant)) {
                $output .= $this->pool->routing->analysisHub(
                    $this->pool->createClass(Model::class)
                        ->setData($constantValue)
                        ->setAdditional($this->retrieveAdditionalData($reflectionConstant))
                        ->setName($constantName)
                        ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC)
                        ->setCustomConnectorLeft($prefix . '::')
                );
            }
        }

        return $output;
    }

    /**
     * Adding visibility infos about hte constant we are analysing.
     *
     * @param \ReflectionClassConstant $reflectionConstant
     *   The reflection of the constant we are analysing.
     *
     * @return string
     *   The visibility info.
     */
    protected function retrieveAdditionalData(ReflectionClassConstant $reflectionConstant): string
    {
        if ($reflectionConstant->isPublic()) {
            return $this->pool->messages->getHelp('publicConstant');
        }

        if ($reflectionConstant->isProtected()) {
            return $this->pool->messages->getHelp('protectedConstant');
        }

        // It either is public, protected or private, and nothing else.
        return $this->pool->messages->getHelp('privateConstant');
    }

    /**
     * Decide, if we can actually dump this one.
     *
     * @param ReflectionClassConstant $reflectionConstant
     *   The name of the constant we want to dump.
     *
     * @return bool
     *   Well? Will we dump it?
     */
    protected function canDump(ReflectionClassConstant $reflectionConstant): bool
    {
        if ($reflectionConstant->isPublic() || $this->isInScope) {
            // It's either public or inside the scope.
            // This includes also some private classes from the highest levels of
            // the class.
            return true;
        }

        // Either a deep private or out of scope.
        return false;
    }
}
