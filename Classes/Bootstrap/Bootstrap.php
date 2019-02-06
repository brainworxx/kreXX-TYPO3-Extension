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

namespace Brainworxx\Includekrexx\Bootstrap;

use TYPO3\CMS\Core\Exception;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * There is no way to clear the cache after an extension update automatically
 * in TYPO3 9.0. To minimize the effect of this, we are doing several things in
 * here:
 *
 * - Load the kreXX files
 * - Register/load the kreXX plugins
 * - Add a check if we need to clear the cache.
 *
 * @package Brainworxx\Includekrexx\Bootstrap
 */
class Bootstrap
{
    /**
     * Our extension key.
     */
    const EXT_KEY = 'includekrexx';

    /**
     * Batch for the bootstrapping.
     */
    public function run()
    {
        if ($this->loadKrexx() === false) {
            // Autoloading failed.
            // There is no point in continuing here.
            return;
        }

        // Register our plugins.
        if (!class_exists('\\Brainworxx\\Krexx\\Service\\Plugin\\Registration')) {
            return;
        }

        // Register and activate the TYPO3 plugin.
        \Brainworxx\Krexx\Service\Plugin\Registration::register(
            'Brainworxx\\Includekrexx\\Plugins\\Typo3\\Configuration'
        );
        \Brainworxx\Krexx\Service\Plugin\Registration::activatePlugin(
            'Brainworxx\\Includekrexx\\Plugins\\Typo3\\Configuration'
        );
        // Register our modules for the admin panel.
        if (version_compare(TYPO3_version, '9.5', '>=')) {
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['debug'])) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['debug']['submodules'] = array_replace_recursive(
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['debug']['submodules'],
                    array(
                        'krexx' => array(
                            'module' => 'Brainworxx\\Includekrexx\\Modules\\Log',
                            'after' => array(
                                'log',
                            ),
                        )
                    )
                );
            }
        }


        // Register the fluid plugins.
        // We activate them later in the viewhelper.
        \Brainworxx\Krexx\Service\Plugin\Registration::register(
            'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Configuration'
        );
        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        // Theoratically, this should be part of the fluid debugger plugin, but
        // activating it in the viewhelper is too late, for obvious reason.
        if (version_compare(TYPO3_version, '8.5', '>=')) {
            if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'] = array(
                    0 => 'Brainworxx\\Includekrexx\\ViewHelpers'
                );
            }
        }
        // Add the legacy debug viewhelper, in case people are using the old krexx
        // namespace.
        if (!class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
            $extPath = ExtensionManagementUtility::extPath(static::EXT_KEY);
            include_once $extPath . 'Classes/ViewHelpers/LegacyDebugViewHelper.php';
        }

        \Brainworxx\Krexx\Service\Plugin\Registration::register(
            'Brainworxx\\Includekrexx\\Plugins\\FluidDataViewer\\Configuration'
        );

        // Register the Aimoes Magic plugin.
        \Brainworxx\Krexx\Service\Plugin\Registration::register(
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\Configuration'
        );

        // Check if we have the Aimeos shop available.
        if (class_exists('Aimeos\\MShop\\Factory') === true ||
            ExtensionManagementUtility::isLoaded('aimeos')
        ) {
            \Brainworxx\Krexx\Service\Plugin\Registration::activatePlugin(
                'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\Configuration'
            );
        }
    }

    /**
     * Clear the cache, if the version number in the ext_localconf is different
     * from the version number of the includekrexx version.
     *
     * @param string $version
     *   The version number from the ext_localconf.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function checkVersionNumber($version)
    {
        if ($version !== ExtensionManagementUtility::getExtensionVersion(static::EXT_KEY)) {
            GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')
                ->flushCachesInGroup('system');
        }

        return $this;
    }

    /**
     * "Autoloading" for the kreXX library.
     *
     * @return bool
     *   Was the "autoloading" successful?
     *   We will not continue with a failed autoloading.
     */
    protected function loadKrexx()
    {
        // There may be a composer verion of kreXX installed.
        // We will not loead the bundeled one.
        if (class_exists('Krexx')) {
            return true;
        }

        // Simply load the main file.
        $krexxFile =  ExtensionManagementUtility::extPath(static::EXT_KEY) . 'Resources/Private/krexx/Krexx.php';
        if (file_exists($krexxFile)) {
            include_once $krexxFile;
            return true;
        }

        // There is a bug with the extension installing (at least in TYPO3 8.7.8),
        // causing this class not being available, right after a manual upgrade.
        // It's not a showstopper, because after a reload, everything is OK.
        // We need to make sure that we have access to the plugin registration, to
        // prevent this ugly TYPO3 error message.
        // Or, something went horribly wrong here.
        return false;
    }
}
