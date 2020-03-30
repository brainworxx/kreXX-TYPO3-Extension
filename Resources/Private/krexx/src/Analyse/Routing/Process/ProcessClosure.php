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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Comment\Functions;
use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use Brainworxx\Krexx\Analyse\Model;
use ReflectionException;
use ReflectionFunction;
use Closure;

/**
 * Processing of closures.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessClosure extends AbstractProcessNoneScalar
{
    /**
     * Is this one a boolean?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this a boolean?
     */
    public function canHandle(Model $model): bool
    {
        return $model->getData() instanceof Closure;
    }

    /**
     * Analyses a closure.
     *
     * @param Model $model
     *   The closure we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected function handleNoneScalar(Model $model): string
    {
        // Remember that we've been here before.
        $this->pool->recursionHandler->addToHive($model->getData());

        try {
            $ref = new ReflectionFunction($model->getData());
        } catch (ReflectionException $e) {
            // Not sure how this can happen.
            return '';
        }

        $result = [];

        // Adding comments from the file.
        $result[static::META_COMMENT] =  $this->pool->createClass(Functions::class)->getComment($ref);

        // Adding the sourcecode
        $result[static::META_SOURCE] = $this->retrieveSourceCode($ref);

        // Adding the place where it was declared.
        $result[static::META_DECLARED_IN] = $this->pool->fileService->filterFilePath($ref->getFileName()) . "\n";
        $result[static::META_DECLARED_IN] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (empty($namespace) === false) {
            $result[static::META_NAMESPACE] = $namespace;
        }

        // Adding the return type.
        $result[static::META_RETURN_TYPE] = $this->pool->createClass(ReturnType::class)->getComment($ref);

        return $this->pool->render->renderExpandableChild($this->dispatchProcessEvent(
            $model->setType(static::TYPE_CLOSURE)
                ->setNormal(static::UNKNOWN_VALUE)
                ->setConnectorParameters($this->retrieveParameterList($ref, $result))
                ->setDomid($this->generateDomIdFromObject($model->getData()))
                ->setConnectorType(Connectors::METHOD)
                ->addParameter(static::PARAM_DATA, $result)
                ->injectCallback($this->pool->createClass(ThroughMeta::class))
        ));
    }

    /**
     * Retrieve the sourcecode of the closure.
     *
     * @param \ReflectionFunction $ref
     *   The reflection of the closure.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function retrieveSourceCode(ReflectionFunction $ref): string
    {
        // Adding the sourcecode
        $highlight = $ref->getStartLine() - 1;
        return $this->pool->fileService->readSourcecode(
            $ref->getFileName(),
            $highlight,
            $highlight - 3,
            $ref->getEndLine() - 1
        );
    }

    /**
     * Retrieve the parameter list of the closure.
     *
     * @param \ReflectionFunction $ref
     *   The reflection of the closure.
     * @param array $result
     *   The result, so far.
     *
     * @return string
     *   Parameter list in a human readable form.
     */
    protected function retrieveParameterList(ReflectionFunction $ref, array &$result): string
    {
        $paramList = '';
        foreach ($ref->getParameters() as $key => $reflectionParameter) {
            ++$key;
            $paramList .=  $result[static::META_PARAM_NO . $key] = $this->pool
                ->codegenHandler
                ->parameterToString($reflectionParameter);
            // We add a comma to the parameter list, to separate them for a
            // better readability.
            $paramList .= ', ';
        }

        return rtrim($paramList, ', ');
    }
}
