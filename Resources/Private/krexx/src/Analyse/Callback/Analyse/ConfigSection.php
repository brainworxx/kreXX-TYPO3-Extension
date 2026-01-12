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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\Model as SettingModel;

/**
 * Configuration "analysis" methods. Meh, naming conventions suck sometimes.
 *
 * @uses array data
 *   The configuration section we are rendering
 */
class ConfigSection extends AbstractCallback implements CallbackConstInterface, ConfigConstInterface
{
    /**
     * Renders each section of the footer.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $sectionOutput = $this->dispatchStartEvent();

        foreach ($this->parameters[static::PARAM_DATA] as $id => $setting) {
            // Render the single value.
            // We need to find out where the value comes from.
            /** @var SettingModel $setting */
            if ($setting->getType() === static::RENDER_TYPE_NONE) {
                // We do not render these.
                continue;
            }

            $sectionOutput .= $this->generateOutput($setting, $id);
        }

        return $sectionOutput;
    }

    /**
     * Generate the output of a single config setting or section.
     *
     * @param \Brainworxx\Krexx\Service\Config\Model $setting
     *   The settings model.
     * @param string $id
     *   The ID of the setting.
     *
     * @return string
     *   The rendered output.
     */
    protected function generateOutput(SettingModel $setting, string $id): string
    {
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)->setHelpid($id . 'Help');
        $name = $this->pool->messages->getHelp($id . 'Readable');
        $value = $this->prepareValue($setting);
        if ($setting->isEditable()) {
            return $this->pool->render->renderSingleEditableChild(
                $model->setData($name)
                    ->setName($value)
                    ->setNormal($setting->getSource())
                    ->setType($setting->getType())->setDomid($id)
            );
        }

        return $this->pool->render->renderExpandableChild(
            $model->setData($value)->setName($name)->setNormal($value)->setType($setting->getSource())
        );
    }

    /**
     * Transform booleans into readable strings.
     *
     * @param \Brainworxx\Krexx\Service\Config\Model $setting
     *   The setting model.
     *
     * @return int|string|null
     *   The prepared value.
     */
    protected function prepareValue(SettingModel $setting)
    {
        $value = $setting->getValue();

        if (!is_bool($value)) {
            // Early return.
            return $value;
        }

        // We need to re-translate booleans to something the frontend can understand.
        return $value ? 'true' : 'false';
    }
}
