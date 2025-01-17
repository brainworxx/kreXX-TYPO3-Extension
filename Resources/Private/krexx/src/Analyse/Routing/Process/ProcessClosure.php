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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Comment\Functions;
use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use Brainworxx\Krexx\Analyse\Model;
use Closure;
use ReflectionException;
use ReflectionFunction;

/**
 * Processing of closures.
 */
class ProcessClosure extends AbstractProcessNoneScalar implements
    ProcessConstInterface,
    CallbackConstInterface,
    ConnectorsConstInterface
{
    /**
     * The model we are currently working on.
     *
     * @var Model
     */
    protected Model $model;

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
        $this->model = $model;
        return $model->getData() instanceof Closure;
    }

    /**
     * Analyses a closure.
     *
     * @return string
     *   The generated markup.
     */
    protected function handleNoneScalar(): string
    {
        /** @var Closure $data */
        $data = $this->model->getData();
        // Remember that we've been here before.
        $this->pool->recursionHandler->addToHive($data);

        try {
            $ref = new ReflectionFunction($data);
        } catch (ReflectionException $e) {
            // Not sure how this can happen.
            return '';
        }

        $result = $this->retrieveMetaData($ref);
        return $this->pool->render->renderExpandableChild($this->dispatchProcessEvent(
            $this->model->setType(static::TYPE_CLOSURE)
                ->setNormal(static::UNKNOWN_VALUE)
                ->setConnectorParameters($this->retrieveParameterList($ref, $result))
                ->setDomid($this->generateDomIdFromObject($data))
                ->setConnectorType(static::CONNECTOR_METHOD)
                ->addParameter(static::PARAM_DATA, $result)
                ->injectCallback($this->pool->createClass(ThroughMeta::class))
        ));
    }

    /**
     * Retrieve the metadata.
     *
     * @param \ReflectionFunction $ref
     *   The reflection of the function we are analysing.
     *
     * @return array
     *   The metadata.
     */
    protected function retrieveMetaData(ReflectionFunction $ref): array
    {
        $result = [];
        $messages = $this->pool->messages;

        // Adding comments from the file.
        $result[$messages->getHelp('metaComment')] = $this->pool
            ->createClass(Functions::class)
            ->getComment($ref);

        // Adding the sourcecode
        $result[$messages->getHelp('metaSource')] = $this->retrieveSourceCode($ref);

        // Adding the place where it was declared.
        $result[$messages->getHelp('metaDeclaredIn')] = $ref->getFileName() . "\n";
        $result[$messages->getHelp('metaDeclaredIn')] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (empty(!$namespace)) {
            $result[$messages->getHelp('metaNamespace')] = $namespace;
        }

        // Adding the return type.
        $result[$messages->getHelp('metaReturnType')] = $this->pool->createClass(ReturnType::class)->getComment($ref);

        return $result;
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
     *   Parameter list in a human-readable form.
     */
    protected function retrieveParameterList(ReflectionFunction $ref, array &$result): string
    {
        $paramList = '';
        foreach ($ref->getParameters() as $key => $reflectionParameter) {
            $paramList .=  $result[$this->pool->messages->getHelp('metaParamNo') . ++$key] = $this->pool
                ->codegenHandler
                ->parameterToString($reflectionParameter);
            // We add a comma to the parameter list, to separate them for a
            // better readability.
            $paramList .= ', ';
        }

        return rtrim($paramList, ', ');
    }
}
