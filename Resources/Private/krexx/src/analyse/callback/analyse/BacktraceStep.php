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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

/**
 * Backtrace analysis methods.
 *
 * The iterate-part takes place in the OutputActions::backtraceAction()
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse
 *
 * @uses array data
 *   The singe step from a backtrace.
 */
class BacktraceStep extends AbstractCallback
{

    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep';

    const STEP_DATA_FILE = 'file';
    const STEP_DATA_LINE = 'line';
    const STEP_DATA_FUNCTION = 'function';
    const STEP_DATA_OBJECT = 'object';
    const STEP_DATA_TYPE = 'type';
    const STEP_DATA_ARGS = 'args';

    /**
     * Renders a backtrace step.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        return $this->dispatchStartEvent() .
            $this->fileToOutput() .
            $this->lineToOutput() .
            $this->functionToOutput() .
            $this->objectToOutput() .
            $this->typeToOutput() .
            $this->argsToOutput();
    }

    /**
     * Analyse the 'file' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function fileToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];
        if (isset($stepData[static::STEP_DATA_FILE]) === true) {
            return $this->pool->render->renderSingleChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($stepData[static::STEP_DATA_FILE])
                        ->setName('File')
                        ->setNormal($stepData[static::STEP_DATA_FILE])
                        ->setType(static::TYPE_STRING . strlen($stepData[static::STEP_DATA_FILE]))
                )
            );
        }

        return '';
    }

    /**
     * Analyse the 'line' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function lineToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];
        $output = '';
        $source = '';
        if (isset($stepData[static::STEP_DATA_LINE]) === true) {
            // Adding the line info to the output
            $output .= $this->pool->render->renderSingleChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($stepData[static::STEP_DATA_LINE])
                    ->setName('Line no.')
                    ->setNormal($stepData[static::STEP_DATA_LINE])
                    ->setType(static::TYPE_INTEGER)
            );

            // Trying the read the sourcecode where it was called.
            $lineNo = $stepData[static::STEP_DATA_LINE] - 1;
            $source = trim(
                $this->pool->fileService->readSourcecode(
                    $stepData[static::STEP_DATA_FILE],
                    $lineNo,
                    $lineNo -5,
                    $lineNo +5
                )
            );
        }

        // Check if we could load the code.
        if (empty($source) === true) {
            $source = $this->pool->messages->getHelp('noSourceAvailable');
        }

        // Add the prettified code to the analysis.
        return $output . $this->pool->render->renderSingleChild(
            $this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($source)
                    ->setName('Sourcecode')
                    ->setNormal('. . .')
                    ->setHasExtra(true)
                    ->setType(static::TYPE_PHP)
            )
        );
    }

    /**
     * Analyse the 'function' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function functionToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];

        if (isset($stepData[static::STEP_DATA_FUNCTION]) === true) {
            return $this->pool->render->renderSingleChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($stepData[static::STEP_DATA_FUNCTION])
                        ->setName('Last called function')
                        ->setNormal($stepData[static::STEP_DATA_FUNCTION])
                        ->setType(static::TYPE_STRING . strlen($stepData[static::STEP_DATA_FUNCTION]))
                )
            );
        }

        return '';
    }

    /**
     * Analyse the 'object' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function objectToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];

        if (isset($stepData[static::STEP_DATA_OBJECT]) === true) {
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessObject')
                ->process($this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($stepData[static::STEP_DATA_OBJECT])
                        ->setName('Calling object')
                ));
        }

        return '';
    }

    /**
     * Analyse the 'type' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function typeToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];

        if (isset($stepData[static::STEP_DATA_TYPE]) === true) {
            return $this->pool->render->renderSingleChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($stepData[static::STEP_DATA_TYPE])
                        ->setName('Call type')
                        ->setNormal($stepData[static::STEP_DATA_TYPE])
                        ->setType(static::TYPE_STRING . strlen($stepData[static::STEP_DATA_TYPE]))
                )
            );
        }

        return '';
    }

    /**
     * Analyse the 'args' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function argsToOutput()
    {
        $stepData = $this->parameters[static::PARAM_DATA];

        if (isset($stepData[static::STEP_DATA_ARGS]) === true) {
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessArray')
                    ->process(
                        $this->dispatchEventWithModel(
                            __FUNCTION__ . static::EVENT_MARKER_END,
                            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                                ->setData($stepData[static::STEP_DATA_ARGS])
                                ->setName('Arguments from the call')
                        )
                    );
        }

        return '';
    }
}
