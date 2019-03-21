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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Controller\AbstractController;
use Brainworxx\Krexx\Service\Config\Fallback;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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

        $config = array();
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            // Stitch together the settings in the template.
            $group = $fallback[Fallback::SECTION];
            $config[$settingsName] = array();
            $config[$settingsName]['name'] = $settingsName;
            $config[$settingsName]['helptext'] = LocalizationUtility::translate(
                $settingsName,
                Bootstrap::EXT_KEY
            );
            $config[$settingsName]['value'] = $this->pool->config->iniConfig->getConfigFromFile($group, $settingsName);
            $config[$settingsName]['useFactorySettings'] = false;
            $config[$settingsName]['fallback'] = $fallback[Fallback::VALUE];

            // Check if we have a value. If not, we need to load the factory
            // settings. We also need to set the info, if we are using the
            // factory settings, at all.
            if (is_null($config[$settingsName]['value'])) {
                // Check if we have a value from the last time a user has saved
                // the settings.
                if ($this->userUc[$settingsName]) {
                    $config[$settingsName]['value'] = $this->userUc[$settingsName];
                } else {
                    // Fallback to the fallback for a possible value.
                    $config[$settingsName]['value'] = $fallback[Fallback::VALUE];
                }
                $config[$settingsName]['useFactorySettings'] = true;
            }

            // Assign the mode-class.
            if (in_array($settingsName, $this->expertOnly) && $config[$settingsName]['useFactorySettings']) {
                $config[$settingsName]['mode'] = 'expert';
            }
        }

        // Adding the dropdown values.
        $dropdown = array();
        $dropdown['skins'] = array();
        foreach ($this->pool->render->getSkinList() as $skin) {
            $dropdown['skins'][$skin] = $skin;
        }
        $dropdown[Fallback::SETTING_DESTINATION] = array(
            'browser' => LocalizationUtility::translate('browser', Bootstrap::EXT_KEY),
            'file' => LocalizationUtility::translate('file', Bootstrap::EXT_KEY),
        );
        $dropdown['bool'] = array(
            'true' => LocalizationUtility::translate('true', Bootstrap::EXT_KEY),
            'false' => LocalizationUtility::translate('false', Bootstrap::EXT_KEY),
        );

        $view->assign('config', $config);
        $view->assign('dropdown', $dropdown);
    }
}
