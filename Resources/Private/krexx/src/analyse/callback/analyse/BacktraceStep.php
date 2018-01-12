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
 * @package Brainworxx\Krexx\Analyse\Callback\Analysis
 *
 * @uses array data
 *   The singe step from a backtrace.
 */
class BacktraceStep extends AbstractCallback
{

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
        return $this->fileToOutput() .
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
        $stepData = $this->parameters['data'];
        if (isset($stepData['file']) === true) {
            return $this->pool->render->renderSingleChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($stepData['file'])
                    ->setName('File')
                    ->setNormal($stepData['file'])
                    ->setType('string ' . strlen($stepData['file']))
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
        $stepData = $this->parameters['data'];
        $output = '';
        $source = '';
        if (isset($stepData['line']) === true) {
            // Adding the line info to the output
            $output .= $this->pool->render->renderSingleChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($stepData['line'])
                    ->setName('Line no.')
                    ->setNormal($stepData['line'])
                    ->setType('integer')
            );

            // Trying the read the sourcecode where it was called.
            $lineNo = $stepData['line'] - 1;
            $source = trim(
                $this->pool->fileService->readSourcecode(
                    $stepData['file'],
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
        $output .= $this->pool->render->renderSingleChild(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($source)
                ->setName('Sourcecode')
                ->setNormal('. . .')
                ->hasExtras()
                ->setType('PHP')
        );

        return $output;
    }

    /**
     * Analyse the 'function' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function functionToOutput()
    {
        $stepData = $this->parameters['data'];

        if (isset($stepData['function']) === true) {
            return $this->pool->render->renderSingleChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($stepData['function'])
                    ->setName('Last called function')
                    ->setNormal($stepData['function'])
                    ->setType('string ' . strlen($stepData['function']))
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
        $stepData = $this->parameters['data'];

        if (isset($stepData['object']) === true) {
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessObject')
                ->process(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setData($stepData['object'])
                        ->setName('Calling object')
                );
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
        $stepData = $this->parameters['data'];

        if (isset($stepData['type']) === true) {
            return $this->pool->render->renderSingleChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($stepData['type'])
                    ->setName('Call type')
                    ->setNormal($stepData['type'])
                    ->setType('string ' . strlen($stepData['type']))
            );
        }
            
        return '';
    }

    /**
     * Analyse the 'type' key from the backtrace step.
     *
     * @return string
     *   The generated dom.
     */
    protected function argsToOutput()
    {
        $stepData = $this->parameters['data'];

        if (isset($stepData['args']) === true) {
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessArray')
                    ->process(
                        $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                            ->setData($stepData['args'])
                            ->setName('Arguments from the call')
                    );
        }

        return '';
    }
}
