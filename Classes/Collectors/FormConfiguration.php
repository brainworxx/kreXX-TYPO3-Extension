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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\From\File;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Collect the current configuration for the frontend option editing.
 *
 * And yes, you can configure the configuration of the configuration.
 */
class FormConfiguration extends AbstractCollector implements ConfigConstInterface, ConstInterface
{
    /**
     * Assigning the form configuration to the view.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    public function assignData(ViewInterface $view)
    {
        if ($this->hasAccess === false) {
            // No access.
            return;
        }

        $dropdown = $this->generateDropdown();

        /** @var File $iniReader */
        $iniReader = $this->pool->createClass(File::class)->loadFile($this->pool->config->getPathToConfigFile());
        $config = [];
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            $config[$settingsName] = [];
            $config[$settingsName][static::SETTINGS_NAME] = $settingsName;
            $config[$settingsName][static::SETTINGS_OPTIONS] = $dropdown;
            $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = false;
            $config[$settingsName][static::SETTINGS_VALUE] = $this->convertKrexxFeSetting(
                $iniReader->getFeConfigFromFile($settingsName)
            );
            $config[$settingsName][static::SETTINGS_FALLBACK] = $dropdown[
                $this->convertKrexxFeSetting($iniReader->feConfigFallback[$settingsName][$iniReader::RENDER])
            ];

            // Check if we have a value. If not, we need to load the
            // factory settings. We also need to set the info, if we
            // are using the factory settings, at all.
            if (is_null($config[$settingsName][static::SETTINGS_VALUE])) {
                $config[$settingsName][static::SETTINGS_VALUE] = $this->convertKrexxFeSetting(
                    $iniReader->feConfigFallback[$settingsName][$iniReader::RENDER]
                );
                $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = true;
            }
        }

        $view->assign('formConfig', $config);
    }

    /**
     * Generate the dropdown array.
     *
     * @return array
     *   The dropdown array.
     */
    protected function generateDropdown(): array
    {
        return [
            static::RENDER_TYPE_CONFIG_FULL => static::translate(
                static::RENDER_TYPE_CONFIG_FULL,
                static::EXT_KEY
            ),
            static::RENDER_TYPE_CONFIG_DISPLAY => static::translate(
                static::RENDER_TYPE_CONFIG_DISPLAY,
                static::EXT_KEY
            ),
            static::RENDER_TYPE_CONFIG_NONE => static::translate(
                static::RENDER_TYPE_CONFIG_NONE,
                static::EXT_KEY
            )
        ];
    }

    /**
     * Converts the kreXX FE config setting.
     *
     * Letting people choose what kind of form element will
     * be used does not really make sense. We will convert the
     * original kreXX settings to a more usable form for the editor.
     *
     * @param array|string|int $values
     *   The values we want to convert.
     *
     * @return string|null
     *   The converted values.
     */
    protected function convertKrexxFeSetting($values)
    {
        if (is_array($values) === false) {
            return null;
        }

        // Explanation:
        // full -> is editable and values will be accepted
        // display -> we will only display the settings
        // The original values include the name of a template partial
        // with the form element.
        if ($values[static::RENDER_TYPE] === static::RENDER_TYPE_NONE) {
            // It's not visible, thus we do not accept any values from it.
            return static::RENDER_TYPE_CONFIG_NONE;
        }

        if ($values[static::RENDER_EDITABLE] === static::VALUE_TRUE) {
            // It's editable and visible.
            return static::RENDER_TYPE_CONFIG_FULL;
        }

        if ($values[static::RENDER_EDITABLE] === static::VALUE_FALSE) {
            // It's only visible.
            return static::RENDER_TYPE_CONFIG_DISPLAY;
        }

        return null;
    }
}
