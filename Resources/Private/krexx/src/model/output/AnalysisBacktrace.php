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

namespace Brainworxx\Krexx\Model\Output;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Analysis\Variables;

/**
 * Backtrace analysis methods.
 *
 * The iterate-part takes place in the OutputActions::backtraceAction()
 *
 * @package Brainworxx\Krexx\Model\Output
 */
class AnalysisBacktrace extends Simple
{
    /**
     * Renders a backtrace step.
     *
     * @return string
     */
    public function renderMe()
    {
        $output = '';
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $stepData = $this->parameters['stepData'];
        // File.
        if (isset($stepData['file'])) {
            $fileModel = new Simple();
            $fileModel->setData($stepData['file'])
                ->setName('File', false)
                ->setNormal($stepData['file'])
                ->setType('string ' . strlen($stepData['file']));

            $output .= OutputActions::$render->renderSingleChild($fileModel);
        }
        // Line.
        if (isset($stepData['line'])) {
            $lineModel = new Simple();
            $lineModel->setData($stepData['line'])
                ->setName('Line no.', false)
                ->setNormal($stepData['line'])
                ->setType('integer');

            $output .= OutputActions::$render->renderSingleChild($lineModel);
        }
        // Sourcecode, is escaped by now.
        if (isset($stepData['sourcecode'])) {
            $sourceModel = new Simple();
            $sourceModel->setData($stepData['sourcecode'], false)
                ->setName('Sourcecode', false)
                ->setNormal('. . .')
                ->setType('PHP');

            $output .= OutputActions::$render->renderSingleChild($sourceModel);
        }
        // Function.
        if (isset($stepData['function'])) {
            $functionModel = new Simple();
            $functionModel->setData($stepData['function'])
                ->setName('Last called function', false)
                ->setNormal($stepData['function'])
                ->setType('string ' . strlen($stepData['function']));

            $output .= OutputActions::$render->renderSingleChild($functionModel);
        }
        // Object.
        if (isset($stepData['object'])) {
            $output .= Variables::analyseObject($stepData['object'], 'Calling object');
        }
        // Type.
        if (isset($stepData['type'])) {
            $typeModel = new Simple();
            $typeModel->setData($stepData['type'])
                ->setName('Call type', false)
                ->setNormal($stepData['type'])
                ->setType('string ' . strlen($stepData['type']));

            $output .= OutputActions::$render->renderSingleChild($typeModel);
        }
        // Args.
        if (isset($stepData['args'])) {
            $output .= Variables::analyseArray($stepData['args'], 'Arguments from the call');
        }

        return $output;
    }
}
