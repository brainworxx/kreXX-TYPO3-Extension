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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\Ini;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class Configuration extends AbstractCollector
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
     * Retrieve the values for the drop downs.
     *
     * @return array
     *   The values for the drop downs.
     */
    protected function retrieveDropDowns(): array
    {
        // Adding the dropdown values.
        $dropdown = [];
        $dropdown['skins'] = [];
        foreach ($this->pool->config->getSkinList() as $skin) {
            $dropdown['skins'][$skin] = $skin;
        }
        $dropdown[Fallback::SETTING_DESTINATION] = [
            Fallback::VALUE_BROWSER => static::translate(Fallback::VALUE_BROWSER, Bootstrap::EXT_KEY),
            Fallback::VALUE_FILE => static::translate(Fallback::VALUE_FILE, Bootstrap::EXT_KEY),
        ];
        $dropdown['bool'] = [
            Fallback::VALUE_TRUE => static::translate(Fallback::VALUE_TRUE, Bootstrap::EXT_KEY),
            Fallback::VALUE_FALSE => static::translate(Fallback::VALUE_FALSE, Bootstrap::EXT_KEY),
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
        /** @var Ini $iniReader */
        $iniReader = $this->pool->createClass(Ini::class)
            ->loadIniFile($this->pool->config->getPathToIniFile());

        $config = [];
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            // Stitch together the settings in the template.
            $group = $fallback[Fallback::SECTION];
            $config[$settingsName] = [];
            $config[$settingsName][static::SETTINGS_NAME] = $settingsName;
            $config[$settingsName][static::SETTINGS_HELPTEXT] = static::translate(
                $settingsName,
                Bootstrap::EXT_KEY
            );
            $config[$settingsName][static::SETTINGS_VALUE] = $iniReader->getConfigFromFile($group, $settingsName);
            $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = false;
            $config[$settingsName][static::SETTINGS_FALLBACK] = $fallback[static::SETTINGS_VALUE];

            // Check if we have a value. If not, we need to load the factory
            // settings. We also need to set the info, if we are using the
            // factory settings, at all.
            if (is_null($config[$settingsName][static::SETTINGS_VALUE])) {
                // Check if we have a value from the last time a user has saved
                // the settings.
                if (isset($this->userUc[$settingsName])) {
                    $config[$settingsName][static::SETTINGS_VALUE] = $this->userUc[$settingsName];
                } else {
                    // Fallback to the fallback for a possible value.
                    $config[$settingsName][static::SETTINGS_VALUE] = $fallback[static::SETTINGS_VALUE];
                }
                $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS] = true;
            }

            // Assign the mode-class.
            if (
                in_array($settingsName, $this->expertOnly) &&
                $config[$settingsName][static::SETTINGS_USE_FACTORY_SETTINGS]
            ) {
                $config[$settingsName][static::SETTINGS_MODE] = 'expert';
            }
        }

        return $config;
    }
}
