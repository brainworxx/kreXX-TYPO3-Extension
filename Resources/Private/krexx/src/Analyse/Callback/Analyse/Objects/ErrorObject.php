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

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Logging\Model as LoggingModel;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;

/**
 * When we are handling an error object, get the backtrace and analyse as such.
 */
class ErrorObject extends AbstractObjectAnalysis implements BacktraceConstInterface
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
     * Error object analysis.
     *
     * @return string
     *   The rendered HTML.
     */
    public function callMe(): string
    {
        // Call the start event, even if this is not an error object.
        $output = $this->dispatchStartEvent() . $this->renderBacktrace();

        /** @var \Throwable $data */
        $data = $this->parameters[static::PARAM_DATA];
        $this->addExceptionMessage(data: $data);
        $lineNo = $data->getLine() - 1;
        $source = trim(string: $this->pool->fileService->readSourcecode(
            filePath: $data->getFile(),
            highlight: $lineNo,
            readFrom: $lineNo - 5,
            readTo: $lineNo + 5
        ));
        if (empty($source)) {
            $source = $this->pool->messages->getHelp(key: 'noSourceAvailable');
        }

        return $output . $this->pool->render->renderExpandableChild(
            model: $this->dispatchEventWithModel(
                name: 'source',
                model: $this->pool->createClass(classname: Model::class)
                    ->setData(data: $source)
                    ->setName(name: $this->pool->messages->getHelp(key: 'sourceCode'))
                    ->setNormal(normal: static::UNKNOWN_VALUE)
                    ->setHasExtra(value: true)
                    ->setType(type: $this->pool->messages->getHelp(key: 'phpType'))
            )
        );
    }

    /**
     * Add a top message for better / faster readability.
     *
     * @param Throwable|LoggingModel $data
     * @return void
     */
    protected function addExceptionMessage(Throwable|LoggingModel $data): void
    {
        // Level 1 means, that is the first object we are looking at.
        if ($this->pool->emergencyHandler->getNestingLevel() !== 1) {
            return;
        }
        $message = $data->getMessage();

        // Some messages are huge.
        if (strlen(string: $message) > 80) {
            $message = substr(string: $message, offset: 0, length: 75) . ' ...';
        }

        // Escape it, there can be some bad stuff in there.
        $message = $this->pool->encodingService->encodeString(data: $message);
        $this->pool->messages
            ->addMessage(key: 'exceptionText', args: [get_class(object: $data), $message], isThrowAway: true);
    }

    /**
     * Retrieve and render the backtrace.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderBacktrace(): string
    {
        $output = '';
        $trace = $this->parameters[static::PARAM_DATA]->getTrace();
        if (is_array(value: $trace)) {
            $this->pool->codegenHandler->setCodegenAllowed(bool: false);
            $output .= $this->pool->render->renderExpandableChild(
                model: $this->dispatchEventWithModel(
                    name: static::TRACE_BACKTRACE,
                    model: $this->pool->createClass(classname: Model::class)
                        ->setName(name: $this->pool->messages->getHelp(key: 'backTrace'))
                        ->setType(type: $this->pool->messages->getHelp(key: 'classInternals'))
                        ->addParameter(name: static::PARAM_DATA, value: $trace)
                        ->injectCallback(
                            object: $this->pool->createClass(classname: ProcessBacktrace::class)
                        )
                )
            );
            $this->pool->codegenHandler->setCodegenAllowed(bool: true);
        }

        return $output;
    }
}
