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

use Brainworxx\Includekrexx\Modules\Log;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Includekrexx\Plugins\FluidDataViewer\Configuration as FluidDataConfiguration;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration as AimeosConfiguration;
use Aimeos\MShop\Exception as AimeosException;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
     * TYPO3 configuration keys.
     */
    const TYPO3_CONF_VARS = 'TYPO3_CONF_VARS';
    const EXTCONF = 'EXTCONF';
    const ADMIN_PANEL = 'adminpanel';
    const MODULES = 'modules';
    const DEBUG = 'debug';
    const SUBMODULES = 'submodules';
    const SYS = 'SYS';
    const FLUID = 'fluid';
    const FLUID_NAMESPACE = 'namespaces';
    const KREXX = 'krexx';

    /**
     * Batch for the bootstrapping.
     */
    public function run()
    {
        if ($this->loadKrexx() === false ||
            class_exists(Registration::class) === false
        ) {
            // "Autoloading" failed.
            // There is no point in continuing here.
            return;
        }

        // Register and activate the TYPO3 plugin.
        $t3configuration = GeneralUtility::makeInstance(T3configuration::class);
        Registration::register($t3configuration);
        Registration::activatePlugin(get_class($t3configuration));
        // Register our modules for the admin panel.
        if (version_compare(TYPO3_version, '9.5', '>=') &&
            isset($GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
                [static::MODULES][static::DEBUG])
        ) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
            [static::MODULES][static::DEBUG][static::SUBMODULES] = array_replace_recursive(
                $GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
                [static::MODULES][static::DEBUG][static::SUBMODULES],
                [static::KREXX => ['module' => Log::class, 'after' => ['log']]]
            );
        }

        // Register the fluid plugins.
        // We activate them later in the viewhelper.
        Registration::register(GeneralUtility::makeInstance(FluidConfiguration::class));
        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        // Theoretically, this should be part of the fluid debugger plugin, but
        // activating it in the viewhelper is too late, for obvious reason.
        if (version_compare(TYPO3_version, '8.5', '>=') &&
            empty($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::FLUID][static::FLUID_NAMESPACE][static::KREXX])
        ) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::FLUID][static::FLUID_NAMESPACE][static::KREXX] = [
                0 => 'Brainworxx\\Includekrexx\\ViewHelpers'
            ];
        }

        // Register the Aimoes Magic plugin.
        $aimeosConfiguration = GeneralUtility::makeInstance(AimeosConfiguration::class);
        Registration::register($aimeosConfiguration);

        // Check if we have the Aimeos shop available.
        if (class_exists(AimeosException::class) === true ||
            ExtensionManagementUtility::isLoaded('aimeos')
        ) {
            Registration::activatePlugin(get_class($aimeosConfiguration));
        }
    }

    /**
     * Clear the cache, if the version number in the ext_localconf is different
     * from the version number of the includekrexx version.
     *
     * @param string $version
     *   The version number from the ext_localconf.
     *
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     * @throws \TYPO3\CMS\Core\Package\Exception
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function checkVersionNumber($version)
    {
        if ($version !== ExtensionManagementUtility::getExtensionVersion(static::EXT_KEY)) {
            GeneralUtility::makeInstance(CacheManager::class)
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
        // There may be a composer version of kreXX installed.
        // We will not load the bundled one.
        if (defined('KREXX_DIR') === true) {
            return true;
        }

        // Simply load the main file.
        $krexxFile =  ExtensionManagementUtility::extPath(static::EXT_KEY) . 'Resources/Private/krexx/bootstrap.php';
        if (file_exists($krexxFile) === true &&
            class_exists(\Krexx::class, false) === false
        ) {
            include_once $krexxFile;
            return true;
        }

        // Something went horribly wrong here.
        // Or . . .
        // More likely, the "autoloading" managed to bite us in the rear end.
        // No need to continue at this point.
        return false;
    }
}
