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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

/**
 * Collect the current configuration for the frontend option editing.
 *
 * And yes, you can configure the configuration of the configuration.
 */
class FormConfiguration extends AbstractCollector implements ConfigConstInterface, ConstInterface
{
    /**
     * @var File
     */
    protected File $fileReader;

    /**
     * Assigning the form configuration to the view.
     *
     * @param \TYPO3\CMS\Fluid\View\AbstractTemplateView|\TYPO3\CMS\Backend\Template\ModuleTemplate $view
     */
    public function assignData($view): void
    {
        if (!$this->hasAccess) {
            // No access.
            return;
        }

        $pathParts = pathinfo($this->pool->config->getPathToConfigFile());
        $this->fileReader = $this->pool->createClass(File::class)
            ->loadFile($pathParts[static::PATHINFO_DIRNAME] . DIRECTORY_SEPARATOR .
                $pathParts[static::PATHINFO_FILENAME] . '.');
        $config = [];
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            $this->generateSingleSetting($settingsName, $config, $this->generateDropdown($fallback));
        }

        $view->assign('formConfig', $config);
    }

    /**
     * Generate a single setting.
     *
     * @param string $settingsName
     *   The name of the setting
     * @param string[][] $config
     *   The configuration so far.
     * @param string[] $dropdown
     *   The pregenerated dropdown.
     */
    protected function generateSingleSetting(string $settingsName, array &$config, array $dropdown): void
    {
        $config[$settingsName] = [];
        $config[$settingsName][static::SETTINGS_NAME] = $settingsName;
        $config[$settingsName][static::SETTINGS_OPTIONS] = $dropdown;
        $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = false;
        $config[$settingsName][static::SETTINGS_VALUE] = $this->convertKrexxFeSetting(
            $this->fileReader->getFeConfigFromFile($settingsName)
        );
        reset($dropdown);
        $config[$settingsName][static::SETTINGS_FALLBACK] = key($dropdown);

        // Check if we have a value. If not, we need to load the
        // factory settings. We also need to set the info, if we
        // are using the factory settings, at all.
        if (is_null($config[$settingsName][static::SETTINGS_VALUE])) {
            $config[$settingsName][static::SETTINGS_VALUE] = $this->convertKrexxFeSetting(
                $this->fileReader->feConfigFallback[$settingsName][$this->fileReader::RENDER]
            );
            $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = true;
        }
    }

    /**
     * Generate the dropdown array.
     *
     * @param array $fallback
     *   The rendering options.
     *
     * @return string[]
     *   The dropdown array.
     */
    protected function generateDropdown(array $fallback): array
    {
        // Yes, we are testing for the string "true", and not for a boolean.
        if ($fallback[static::RENDER][static::RENDER_EDITABLE] === static::VALUE_TRUE) {
            return [
                static::RENDER_TYPE_CONFIG_FULL => static::translate(static::RENDER_TYPE_CONFIG_FULL),
                static::RENDER_TYPE_CONFIG_DISPLAY => static::translate(static::RENDER_TYPE_CONFIG_DISPLAY),
                static::RENDER_TYPE_CONFIG_NONE => static::translate(static::RENDER_TYPE_CONFIG_NONE)
            ];
        }

        if ($fallback[static::RENDER][static::RENDER_TYPE] !== static::RENDER_TYPE_NONE) {
            return [
                static::RENDER_TYPE_CONFIG_DISPLAY => static::translate(static::RENDER_TYPE_CONFIG_DISPLAY),
                static::RENDER_TYPE_CONFIG_NONE => static::translate(static::RENDER_TYPE_CONFIG_NONE)
            ];
        }

        return [
            static::RENDER_TYPE_CONFIG_NONE => static::translate(static::RENDER_TYPE_CONFIG_NONE)
        ];
    }

    /**
     * Converts the kreXX FE config setting.
     *
     * Letting people choose what kind of form element will
     * be used does not really make sense. We will convert the
     * original kreXX settings to a more usable form for the editor.
     *
     * @param string[]|string|int $values
     *   The values we want to convert.
     *
     * @return string|null
     *   The converted values.
     */
    protected function convertKrexxFeSetting($values): ?string
    {
        if (!is_array($values)) {
            return null;
        }

        // Explanation:
        // full -> is editable and values will be accepted
        // display -> we will only display the settings
        // The original values include the name of a template partial
        // with the form element.
        $result = null;
        if ($values[static::RENDER_TYPE] === static::RENDER_TYPE_NONE) {
            // It's not visible, thus we do not accept any values from it.
            $result = static::RENDER_TYPE_CONFIG_NONE;
        } elseif ($values[static::RENDER_EDITABLE] === static::VALUE_TRUE) {
            // It's editable and visible.
            $result = static::RENDER_TYPE_CONFIG_FULL;
        } elseif ($values[static::RENDER_EDITABLE] === static::VALUE_FALSE) {
            // It's only visible.
            $result = static::RENDER_TYPE_CONFIG_DISPLAY;
        }

        return $result;
    }
}
