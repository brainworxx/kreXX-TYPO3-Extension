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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Logging\Model as LoggingModel;
use Throwable;

/**
 * When we are handling an error object, get the backtrace and analyse as such.
 */
class ErrorObject extends AbstractObjectAnalysis implements BacktraceConstInterface
{
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
        $this->addExceptionMessage($data);
        $lineNo = $data->getLine() - 1;
        $source = trim($this->pool->fileService->readSourcecode($data->getFile(), $lineNo, $lineNo - 5, $lineNo + 5));
        if (empty($source)) {
            $source = $this->pool->messages->getHelp('noSourceAvailable');
        }

        return $output . $this->pool->render->renderExpandableChild(
            $this->dispatchEventWithModel(
                'source',
                $this->pool->createClass(Model::class)
                    ->setData($source)
                    ->setName($this->pool->messages->getHelp('sourceCode'))
                    ->setNormal(static::UNKNOWN_VALUE)
                    ->setHasExtra(true)
                    ->setType(static::TYPE_PHP)
            )
        );
    }

    /**
     * Add a top message for better / faster readability.
     *
     * @param Throwable|LoggingModel $data
     * @return void
     */
    protected function addExceptionMessage($data): void
    {
        // Level 1 means, that is the first object we are looking at.
        if ($this->pool->emergencyHandler->getNestingLevel() !== 1) {
            return;
        }
        $message = $data->getMessage();

        // Some messages are huge.
        if (strlen($message) > 80) {
            $message = substr($message, 0, 75) . ' ...';
        }

        // Escape it, there can be some bad stuff in there.
        $message = $this->pool->encodingService->encodeString($message);
        $this->pool->messages->addMessage('exceptionText', [get_class($data), $message], true);
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
        if (is_array($trace)) {
            $this->pool->codegenHandler->setCodegenAllowed(false);
            $output .= $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    static::TRACE_BACKTRACE,
                    $this->pool->createClass(Model::class)
                        ->setName($this->pool->messages->getHelp('backTrace'))
                        ->setType($this->pool->messages->getHelp('classInternals'))
                        ->addParameter(static::PARAM_DATA, $trace)
                        ->injectCallback(
                            $this->pool->createClass(ProcessBacktrace::class)
                        )
                )
            );
            $this->pool->codegenHandler->setCodegenAllowed(true);
        }

        return $output;
    }
}
