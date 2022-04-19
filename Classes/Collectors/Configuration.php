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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
use TYPO3\CMS\Core\Log\LogLevel;

/**
 * Collect the current configuration for the backend module.
 */
class Configuration extends AbstractCollector implements ConfigConstInterface, ConstInterface
{
    /**
     * Assign the kreXX configuration for the view.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    public function assignData(ViewInterface $view)
    {
        if ($this->hasAccess === false) {
            // No access.
            return;
        }

        $view->assign('config', $this->retrieveConfiguration());
        $view->assign('dropdown', $this->retrieveDropDowns());
    }

    /**
     * Retrieve the values for the drop-downs.
     *
     * @return array
     *   The values for the drop-downs.
     */
    protected function retrieveDropDowns(): array
    {
        // Adding the dropdown values.
        $dropdown = [];
        $dropdown['skins'] = [];
        foreach ($this->pool->config->getSkinList() as $skin) {
            $dropdown['skins'][$skin] = $skin;
        }
        $dropdown[static::SETTING_DESTINATION] = [
            static::VALUE_BROWSER => static::translate(static::VALUE_BROWSER, static::EXT_KEY),
            static::VALUE_FILE => static::translate(static::VALUE_FILE, static::EXT_KEY),
        ];
        $dropdown['bool'] = [
            static::VALUE_TRUE => static::translate(static::VALUE_TRUE, static::EXT_KEY),
            static::VALUE_FALSE => static::translate(static::VALUE_FALSE, static::EXT_KEY),
        ];
        $dropdown['loglevel'] = [
            LogLevel::DEBUG => static::translate('loglevel.debug', static::EXT_KEY),
            LogLevel::INFO => static::translate('loglevel.info', static::EXT_KEY),
            LogLevel::NOTICE => static::translate('loglevel.notice', static::EXT_KEY),
            LogLevel::WARNING => static::translate('loglevel.warning', static::EXT_KEY),
            LogLevel::ERROR => static::translate('loglevel.error', static::EXT_KEY),
            LogLevel::CRITICAL => static::translate('loglevel.critical', static::EXT_KEY),
            LogLevel::ALERT => static::translate('loglevel.alert', static::EXT_KEY),
            LogLevel::EMERGENCY => static::translate('loglevel.emergency', static::EXT_KEY),
        ];

        return $dropdown;
    }

    /**
     * Retrieve the ini configuration, like the method name implies.
     *
     * @return array
     *   The configuration array for the view
     */
    protected function retrieveConfiguration(): array
    {
        /** @var File $iniReader */
        $iniReader = $this->pool->createClass(File::class)
            ->loadFile($this->pool->config->getPathToConfigFile());

        $config = [];
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            // Stitch together the settings in the template.
            $group = $fallback[static::SECTION];
            $config[$settingsName] = [];
            $config[$settingsName][static::SETTINGS_NAME] = $settingsName;
            $config[$settingsName][static::SETTINGS_VALUE] = $iniReader->getConfigFromFile($group, $settingsName);
            $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = false;
            $config[$settingsName][static::SETTINGS_FALLBACK] = $fallback[static::SETTINGS_VALUE];
            $this->applyFallbackToConfig($config, $settingsName, $fallback);
        }

        return $config;
    }

    /**
     * Check if we have a value.
     *
     * If not, we need to load the factory settings. We also need to set the
     * info, if we are using the factory settings, at all.
     *
     * @param array $config
     *   The configuration array, so far.
     * @param string $settingsName
     *   The name of the setting we are processing right now.
     * @param array $fallback
     *   The fallback values.
     */
    protected function applyFallbackToConfig(array &$config, string $settingsName, array $fallback)
    {
        // Check if we have a value. If not, we need to load the factory
        // settings. We also need to set the info, if we are using the
        // factory settings, at all.
        if ($config[$settingsName][static::SETTINGS_VALUE] !== null) {
            // We have a setting, and are not afraid to use it.
            return;
        }

        // Check if we have a value from the last time a user has saved
        // the settings.
        $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = true;

        $config[$settingsName][static::SETTINGS_VALUE] = isset($this->userUc[$settingsName]) === true ?
            $this->userUc[$settingsName] : $fallback[static::SETTINGS_VALUE];

        // Assign the mode-class.
        if (in_array($settingsName, $this->expertOnly) === true) {
            $config[$settingsName][static::SETTINGS_MODE] = 'expert';
        }
    }
}
