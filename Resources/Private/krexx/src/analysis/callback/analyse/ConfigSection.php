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
 * Configuration "analysis" methods. Meh, naming conventions suck sometimes.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analysis
 *
 * @uses array data
 *   The configuration section we are rendering
 * @uses array source
 *   The info of the source if the configuration
 *   fallback, file, cookie.
 */
class ConfigSection extends AbstractCallback
{

    /**
     * Renders each section of the footer.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $sectionOutput = '';
        foreach ($this->parameters['data'] as $name => $setting) {
            // Render the single value.
            // We need to find out where the value comes from.
            /** @var \Brainworxx\Krexx\Service\Config\Model $setting */
            $value = $setting->getValue();
            if ($setting->getType() != 'None') {
                // We need to re-translate booleans to something the
                // frontend can understand.
                if ($value === true) {
                    $value = 'true';
                }
                if ($value === false) {
                    $value = 'false';
                }

                $model = new Model($this->storage);
                if ($setting->getEditable()) {
                    $model->setData($name)
                        ->setName($value)
                        ->setNormal($setting->getSource())
                        ->setType($setting->getType())
                        ->setHelpid($name);

                    $sectionOutput .= $this->storage->render->renderSingleEditableChild($model);
                } else {
                    $model->setData($value)
                        ->setName($name)
                        ->setNormal($value)
                        ->setType($setting->getSource())
                        ->setHelpid($name);
                    $sectionOutput .= $this->storage->render->renderSingleChild($model);
                }
            }
        }
        return $sectionOutput;
    }
}
