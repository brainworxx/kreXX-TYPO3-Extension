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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;

/**
 * Backtrace analysis methods.
 *
 * The iterate-part takes place in the OutputActions::backtraceAction()
 *
 * @uses array data
 *   The singe step from a backtrace.
 * @uses string metaname
 *   The unfiltered file name where the step happened.
 */
class BacktraceStep extends AbstractCallback implements
    BacktraceConstInterface,
    CallbackConstInterface,
    ProcessConstInterface
{
    /**
     * Renders a backtrace step.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $messages = $this->pool->messages;
        return $this->dispatchStartEvent() .
            $this->outputSingleChild($messages->getHelp('file'), static::TRACE_FILE, 'fileToOutput') .
            $this->lineToOutput() .
            $this->outputProcessor(
                $messages->getHelp('callingObject'),
                static::TRACE_OBJECT,
                'objectToOutput',
                ProcessObject::class
            ) . $this->outputSingleChild(
                $messages->getHelp('callType'),
                static::TRACE_TYPE,
                'typeToOutput'
            ) . $this->outputSingleChild(
                $messages->getHelp('lastCalledFunction'),
                static::TRACE_FUNCTION,
                'functionToOutput'
            ) . $this->outputProcessor(
                $messages->getHelp('argumentsFromTheCall'),
                static::TRACE_ARGS,
                'argsToOutput',
                ProcessArray::class
            );
    }

    /**
     * Analyse the 'line' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function lineToOutput(): string
    {
        $model = $this->pool->createClass(Model::class)
            ->setName($this->pool->messages->getHelp('sourceCode'))
            ->setNormal(static::UNKNOWN_VALUE)
            ->setHasExtra(true)
            ->setType(static::TYPE_PHP);

        return $this->retrieveSource($model) . $this->pool->render->renderExpandableChild(
            $this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $model
            )
        );
    }

    /**
     * Retrieve the sourcecode and render it with some metadata.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, where we assign the code.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function retrieveSource(Model $model): string
    {
        $stepData = $this->parameters[static::PARAM_DATA];
        $output = '';

        if (isset($stepData[static::TRACE_LINE])) {
            // Adding the line info to the output
            $output = $this->pool->render->renderExpandableChild(
                $this->pool->createClass(Model::class)
                    ->setData($stepData[static::TRACE_LINE])
                    ->setName($this->pool->messages->getHelp('lineNumber'))
                    ->setNormal($stepData[static::TRACE_LINE])
                    ->setType(static::TYPE_INTEGER)
            );

            // Trying the read the sourcecode where it was called.
            $lineNo = $stepData[static::TRACE_LINE] - 1;
            $source = trim(
                $this->pool->fileService->readSourcecode(
                    $stepData[static::TRACE_FILE] ?? '',
                    $lineNo,
                    $lineNo - 5,
                    $lineNo + 5
                )
            );
        }

        // Check if we could load the code.
        if (empty($source)) {
            $source = $this->pool->messages->getHelp('noSourceAvailable');
        }
        $model->setData($source);

        return $output;
    }

    /**
     * Directly render the output by a processor.
     *
     * @param string $name
     *   The human-readable name of what we are rendering
     * @param string $type
     *   The array key inside the backtrace
     * @param string $eventName
     *   The event name of what we dispatch.
     * @param string $processorName
     *   The class name of the processor.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function outputProcessor(string $name, string $type, string $eventName, string $processorName): string
    {
        $stepData = $this->parameters[static::PARAM_DATA];
        if (!isset($stepData[$type])) {
            return '';
        }

        $processor = $this->pool->createClass($processorName);
        $model = $this->dispatchEventWithModel(
            $eventName . static::EVENT_MARKER_END,
            $this->pool->createClass(Model::class)->setData($stepData[$type])->setName($name)
        );

        $processor->canHandle($model);
        return $processor->handle();
    }

    /**
     * Render a single child of the backtrace.
     *
     * @param string $name
     *   The human-readable name of what we are rendering
     * @param string $type
     *   The array key inside the backtrace
     * @param string $eventName
     *   The event name of what we dispatch.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function outputSingleChild(string $name, string $type, string $eventName): string
    {
        $stepData = $this->parameters[static::PARAM_DATA];
        if (isset($stepData[$type])) {
            return $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    $eventName . static::EVENT_MARKER_END,
                    $this->pool->createClass(Model::class)
                        ->setData($stepData[$type])
                        ->setName($name)
                        ->setNormal($stepData[$type])
                        ->setType(static::TYPE_STRING)
                )
            );
        }
        return '';
    }
}
