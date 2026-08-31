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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Comment\Comment;
use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
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
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

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
        $this->pool->recursionHandler->addToHive(bee: $data);

        try {
            $ref = new ReflectionFunction(function: $data);
        } catch (ReflectionException) {
            // Not sure how this can happen.
            return '';
        }

        $result = $this->retrieveMetaData(ref: $ref);
        return $this->pool->render->renderExpandableChild(model: $this->dispatchProcessEvent(
            model: $this->model->setType(type: static::TYPE_CLOSURE)
                ->setNormal(normal: static::UNKNOWN_VALUE)
                ->setConnectorParameters(params: $this->retrieveParameterList(ref: $ref, result: $result))
                ->setDomid(domid: $this->generateDomIdFromObject(data: $data))
                ->setConnectorType(type: static::CONNECTOR_METHOD)
                ->addParameter(name: static::PARAM_DATA, value: $result)
                ->injectCallback(object: $this->pool->createClass(classname: ThroughMeta::class))
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
        $result[$messages->getHelp(key: 'metaComment')] = $this->pool
            ->createClass(classname: Comment::class)
            ->getComment(reflection: $ref);

        // Adding the sourcecode
        $result[$messages->getHelp(key: 'metaSource')] = $this->retrieveSourceCode($ref);

        // Adding the place where it was declared.
        $result[$messages->getHelp(key: 'metaDeclaredIn')] = $ref->getFileName() . "\n";
        $result[$messages->getHelp(key: 'metaDeclaredIn')] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if ($namespace) {
            $result[$messages->getHelp(key: 'metaNamespace')] = $namespace;
        }

        // Adding the return type.
        $result[$messages->getHelp(key: 'metaReturnType')] = $this->pool->createClass(classname: ReturnType::class)
            ->getComment(reflection: $ref);

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
            filePath: $ref->getFileName(),
            highlight: $highlight,
            readFrom: $highlight - 3,
            readTo: $ref->getEndLine() - 1
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
            $paramList .=  $result[$this->pool->messages->getHelp(key: 'metaParamNo') . ++$key] = $this->pool
                ->codegenHandler->parameterToString(reflectionParameter: $reflectionParameter);
            // We add a comma to the parameter list, to separate them for a
            // better readability.
            $paramList .= ', ';
        }

        return rtrim(string: $paramList, characters: ', ');
    }
}
