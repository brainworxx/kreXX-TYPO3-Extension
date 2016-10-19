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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Model;

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
        $output = '';
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $stepData = $this->parameters['data'];
        // File.
        if (isset($stepData['file'])) {
            $fileModel = new Model($this->storage);
            $fileModel->setData($stepData['file'])
                ->setName('File')
                ->setNormal($stepData['file'])
                ->setType('string ' . strlen($stepData['file']));

            $output .= $this->storage->render->renderSingleChild($fileModel);
        }
        // Line.
        if (isset($stepData['line'])) {
            $lineModel = new Model($this->storage);
            $lineModel->setData($stepData['line'])
                ->setName('Line no.')
                ->setNormal($stepData['line'])
                ->setType('integer');

            $output .= $this->storage->render->renderSingleChild($lineModel);
        }

        // Sourcecode, is escaped by now.
        $sourceModel = new Model($this->storage);
        if (isset($stepData['line'])) {
            $lineNo = $stepData['line'] + $this->parameters['offset'];
            $source = trim($this->storage->readSourcecode($stepData['file'], $lineNo, $lineNo -5, $lineNo +5));
            if (empty($source)) {
                $source = $this->storage->messages->getHelp('noSourceAvailable');
            }
        } else {
            $source = $this->storage->messages->getHelp('noSourceAvailable');
        }

        $sourceModel->setData($source)
            ->setName('Sourcecode')
            ->setNormal('. . .')
            ->hasExtras()
            ->setType('PHP');
        $output .= $this->storage->render->renderSingleChild($sourceModel);

        // Function.
        if (isset($stepData['function'])) {
            $functionModel = new Model($this->storage);
            $functionModel->setData($stepData['function'])
                ->setName('Last called function')
                ->setNormal($stepData['function'])
                ->setType('string ' . strlen($stepData['function']));

            $output .= $this->storage->render->renderSingleChild($functionModel);
        }
        // Object.
        if (isset($stepData['object'])) {
            $objectModel = new Model($this->storage);
            $objectModel->setData($stepData['object'])
                ->setName('Calling object');
            $output .= $this->storage->routing->analyseObject($objectModel);
        }
        // Type.
        if (isset($stepData['type'])) {
            $typeModel = new Model($this->storage);
            $typeModel->setData($stepData['type'])
                ->setName('Call type')
                ->setNormal($stepData['type'])
                ->setType('string ' . strlen($stepData['type']));

            $output .= $this->storage->render->renderSingleChild($typeModel);
        }
        // Args.
        if (isset($stepData['args'])) {
            $argsModel = new Model($this->storage);
            $argsModel->setData($stepData['args'])
                ->setName('Arguments from the call');
            $output .= $this->storage->routing->analyseArray($argsModel);
        }

        return $output;
    }
}
