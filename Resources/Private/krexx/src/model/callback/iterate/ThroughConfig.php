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

namespace Brainworxx\Krexx\Model\Callback\Iterate;

use Brainworxx\Krexx\Model\Callback\AbstractCallback;
use Brainworxx\Krexx\Model\Simple;

/**
 * Configuration output methods.
 *
 * @package Brainworxx\Krexx\Model\Callback\Iterate
 *
 * @uses array config
 *   The configuration section we are rendering
 * @uses array source
 *   The info of the source if the configuration
 *   fallback, file, cookie.
 */
class ThroughConfig extends AbstractCallback
{
    /**
     * Renders whole configuration.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $config = $this->parameters['config'];
        $source = $this->parameters['source'];
        $configOutput = '';
        foreach ($config as $sectionName => $sectionData) {
            // Render a whole section.
            $model = new Simple($this->storage);
            $model->setName($sectionName)
                ->setType('Config')
                ->setAdditional('. . .')
                ->addParameter('sectionData', $sectionData)
                ->addParameter('source', $source[$sectionName])
                ->initCallback('Analyse\ConfigSection');

            $configOutput .= $this->storage->render->renderExpandableChild($model);
        }
        // Render the dev-handle field.
        $editableModel = new Simple($this->storage);
        $data = 'Local open function';
        $editableModel->setData($data)
            ->setName($this->storage->config->getDevHandler())
            ->setNormal('\krexx::')
            ->setType('Input')
            ->setHelpid('localFunction');

        $configOutput .= $this->storage->render->renderSingleEditableChild($editableModel);
        // Render the reset-button which will delete the debug-cookie.
        $buttonModel = new Simple($this->storage);
        $buttonModel->setName('resetbutton')
            ->setNormal('Reset local settings')
            ->setHelpid('resetbutton');

        $configOutput .= $this->storage->render->renderButton($buttonModel);
        return $configOutput;
    }
}
