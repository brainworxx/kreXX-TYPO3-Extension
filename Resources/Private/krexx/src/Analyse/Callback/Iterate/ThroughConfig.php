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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Configuration output methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses null
 *   There are no parameters available here.
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
        $configOutput = $this->dispatchStartEvent() . $this->renderAllSections();

        // Render the dev-handle field.
        $devHandleLabel = $this->pool->messages->getHelp(Fallback::SETTING_DEV_HANDLE);
        $configOutput .= $this->pool->render->renderSingleEditableChild(
            $this->pool->createClass(Model::class)
                ->setData($devHandleLabel)
                ->setDomId(Fallback::SETTING_DEV_HANDLE)
                ->setName($this->pool->config->getDevHandler())
                ->setNormal('\krexx::')
                ->setType(Fallback::RENDER_TYPE_INPUT)
                ->setHelpid('localFunction')
        );

        // Render the reset-button which will delete the debug-cookie.
        return $configOutput . $this->pool->render->renderButton(
            $this->pool->createClass(Model::class)
                ->setName('kresetbutton')
                ->setNormal('Reset local settings')
                ->setHelpid('kresetbutton')
        );
    }

    /**
     * Render the configuration sections.
     *
     * @return string
     *   The output html.
     */
    protected function renderAllSections()
    {
        // We need to "explode" our config array into the
        // sections again, for better readability.
        $sections = [];
        foreach ($this->pool->config->settings as $name => $setting) {
            $sections[$setting->getSection()][$name] = $setting;
        }

        $configOutput = '';
        foreach ($sections as $sectionName => $sectionData) {
            // Render a whole section.
            $configOutput .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass(Model::class)
                    ->setName($this->pool->messages->getHelp($sectionName . 'Readable'))
                    ->setType(static::TYPE_CONFIG)
                    ->setNormal(static::UNKNOWN_VALUE)
                    ->addParameter(static::PARAM_DATA, $sectionData)
                    ->injectCallback(
                        $this->pool->createClass(ConfigSection::class)
                    )
            );
        }

        return $configOutput;
    }
}
