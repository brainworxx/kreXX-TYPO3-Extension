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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\File;

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
     * The file service, used to readand write files.
     *
     * @var File
     */
    protected $fileService;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->fileService = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');
    }

    /**
     * Renders a backtrace step.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $stepData = $this->parameters['data'];
        // File.
        if (isset($stepData['file'])) {
            $fileModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['file'])
                ->setName('File')
                ->setNormal($stepData['file'])
                ->setType('string ' . strlen($stepData['file']));

            $output .= $this->pool->render->renderSingleChild($fileModel);
        }
        // Line.
        if (isset($stepData['line'])) {
            $lineModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['line'])
                ->setName('Line no.')
                ->setNormal($stepData['line'])
                ->setType('integer');

            $output .= $this->pool->render->renderSingleChild($lineModel);
        }

        // Sourcecode, is escaped by now.

        if (isset($stepData['line'])) {
            $lineNo = $stepData['line'] + $this->parameters['offset'];
            $source = trim($this->fileService->readSourcecode($stepData['file'], $lineNo, $lineNo -5, $lineNo +5));
            if (empty($source)) {
                $source = $this->pool->messages->getHelp('noSourceAvailable');
            }
        } else {
            $source = $this->pool->messages->getHelp('noSourceAvailable');
        }
        $sourceModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
            ->setData($source)
            ->setName('Sourcecode')
            ->setNormal('. . .')
            ->hasExtras()
            ->setType('PHP');
        $output .= $this->pool->render->renderSingleChild($sourceModel);

        // Function.
        if (isset($stepData['function'])) {
            $functionModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['function'])
                ->setName('Last called function')
                ->setNormal($stepData['function'])
                ->setType('string ' . strlen($stepData['function']));

            $output .= $this->pool->render->renderSingleChild($functionModel);
        }
        // Object.
        if (isset($stepData['object'])) {
            $objectModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['object'])
                ->setName('Calling object');
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessObject')
                ->process($objectModel);
        }
        // Type.
        if (isset($stepData['type'])) {
            $typeModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['type'])
                ->setName('Call type')
                ->setNormal($stepData['type'])
                ->setType('string ' . strlen($stepData['type']));

            $output .= $this->pool->render->renderSingleChild($typeModel);
        }
        // Args.
        if (isset($stepData['args'])) {
            $argsModel = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($stepData['args'])
                ->setName('Arguments from the call');
            $output .= $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessArray')
                ->process($argsModel);
        }

        return $output;
    }
}
