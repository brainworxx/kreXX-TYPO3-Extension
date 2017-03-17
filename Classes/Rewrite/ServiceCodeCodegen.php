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

use Brainworxx\Krexx\Service\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;

class Tx_Includekrexx_Rewrite_ServiceCodeCodegen extends Codegen
{
    const VHS_CALL_VIEWHELPER = 'vhs_call_viewhelper';


    /**
     * We are only handling the VHS Call VireHelper generation here.
     * Everything els will be done by the parent class.
     *
     * {@inheritdoc}
     */
    public function generateSource(Model $model)
    {
        // Get out of here as soon as possible.
        if (!$this->allowCodegen) {
            return '';
        }

        // check for VHS values.
        if ($model->getMultiLineCodeGen() === self::VHS_CALL_VIEWHELPER) {
            $this->counter++;
            return $this->generateVhsCall($model);
        } else {
            // Do the parent generation. [ParentGeneration = Generation X ?]
            return parent::generateSource($model);
        }
    }

    /**
     * We are generation fluid inline code, usinf the CHS Call ViewHelper
     *
     * @example
     *   object -> v:call(method: 'functionname', arguments: {arg1: 'parmeter1', arg2: 'parameter2'})
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model for which we are generation the source code.
     *
     * @return string
     *   The generated fluid source.
     */
    protected function generateVhsCall(Model $model)
    {
        $data = $model->getParameters();
        $counter = 1;
        $args = '';

        foreach ($data['data'] as $resultName => $analysisResultLine) {
            if (is_a($analysisResultLine, 'Brainworxx\\Krexx\\Service\\Code\\ReflectionParameterWrapper')) {
                $args .= 'arg' . $counter . ': \'' . $analysisResultLine->getParameterName() . '\', ';
                $counter++;
            }
        }

        $firstPart = ' -> v:call(method: \'';
        $secondPart = '\', arguments: {';
        $lastPart = '})';
        $alternativeLastPart = '\')';

        if ($counter >= 2) {
            return $firstPart . $model->getName() . $secondPart . rtrim($args, ', ') . $lastPart;
        } else {
            return $firstPart . $model->getName() . $alternativeLastPart;
        }

    }
}