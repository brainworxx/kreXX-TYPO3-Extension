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
use Brainworxx\Krexx\Config\Config;

/**
 * Configuration output methods.
 *
 * @package Brainworxx\Krexx\Model\Output
 */
class IterateThroughConfig extends Simple
{
    /**
     * Renders the footer.
     *
     * @return string
     */
    public function renderMe()
    {
        $config = $this->parameters['config'];
        $source = $this->parameters['source'];
        $configOutput = '';
        foreach ($config as $sectionName => $sectionData) {
            // Render a whole section.
            $model = new AnalysisConfig();
            $model->setName($sectionName)
                ->setType('Config')
                ->setAdditional('. . .')
                ->addParameter('sectionData', $sectionData)
                ->addParameter('source', $source[$sectionName]);
            $configOutput .= OutputActions::$render->renderExpandableChild($model);
        }
        // Render the dev-handle field.
        $editableModel = new Simple();
        $data = 'Local open function';
        $editableModel->setData($data)
            ->setName(Config::getDevHandler())
            ->setNormal('\krexx::')
            ->setType('Input')
            ->setHelpid('localFunction');

        $configOutput .= OutputActions::$render->renderSingleEditableChild($editableModel);
        // Render the reset-button which will delete the debug-cookie.
        $buttonModel = new Simple();
        $buttonModel->setName('resetbutton', false)
            ->setNormal('Reset local settings')
            ->setHelpid('resetbutton');

        $configOutput .= OutputActions::$render->renderButton($buttonModel);
        return $configOutput;
    }
}
