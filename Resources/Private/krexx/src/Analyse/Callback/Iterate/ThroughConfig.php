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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Configuration output methods.
 *
 * @uses null
 *   There are no parameters available here.
 */
class ThroughConfig extends AbstractCallback implements CallbackConstInterface, ConfigConstInterface
{
    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Renders whole configuration.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        return $this->dispatchStartEvent() . $this->renderAllSections() .
            $this->pool->render->renderButton(
                model: $this->pool->createClass(classname: Model::class)
                    ->setName(name: 'kresetbutton')
                    ->setNormal(normal: $this->pool->messages->getHelp(key: 'resetCookiesReadable'))
                    ->setHelpid(helpId: 'kresetbutton')
            );
    }

    /**
     * Render the configuration sections.
     *
     * @return string
     *   The output html.
     */
    protected function renderAllSections(): string
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
            if ($this->hasSomethingToRender(sectionData: $sectionData)) {
                $configOutput .= $this->pool->render->renderExpandableChild(
                    model: $this->pool->createClass(classname: Model::class)
                        ->setName(name: $this->pool->messages->getHelp(key: $sectionName . 'Readable'))
                        ->setType(type: $this->pool->messages->getHelp(key: 'configType'))
                        ->setNormal(normal: static::UNKNOWN_VALUE)
                        ->addParameter(name: static::PARAM_DATA, value: $sectionData)
                        ->injectCallback(
                            object: $this->pool->createClass(classname: ConfigSection::class)
                        )
                );
            }
        }

        return $configOutput;
    }

    /**
     * Is there anything to render in this config seaction?
     *
     * @param \Brainworxx\Krexx\Service\Config\Model[] $sectionData
     *   The section data we want to render.
     *
     * @return bool
     *   Well? Is there anything to render at all?
     */
    protected function hasSomethingToRender(array $sectionData): bool
    {
        foreach ($sectionData as $setting) {
            if ($setting->getType() !== static::RENDER_TYPE_NONE) {
                return true;
            }
        }

        return false;
    }
}
