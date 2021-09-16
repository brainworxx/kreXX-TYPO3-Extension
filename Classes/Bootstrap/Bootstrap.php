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

namespace Brainworxx\Includekrexx\Bootstrap;

use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration as AimeosConfiguration;
use Aimeos\MShop\Exception as AimeosException;
use Throwable;
use Krexx;

/**
 * There is no way to clear the cache after an extension update automatically
 * in TYPO3 9.0. To minimize the effect of this, we are doing several things in
 * here:
 *
 * - Load the kreXX files
 * - Register/load the kreXX plugins
 * - Add a check if we need to clear the cache.
 */
class Bootstrap implements ConstInterface
{
    /**
     * The TYPO3 version.
     *
     * @var string
     */
    protected static $typo3Version;

    /**
     * Batch for the bootstrapping.
     */
    public function run()
    {
        if ($this->loadKrexx() === false) {
            // "Autoloading" failed.
            // There is no point in continuing here.
            return;
        }

        $this->retrieveTypo3Version();

        // Register and activate the TYPO3 plugin.
        /** @var T3configuration $t3configuration */
        $t3configuration = GeneralUtility::makeInstance(T3configuration::class);
        Registration::register($t3configuration);
        Registration::activatePlugin(get_class($t3configuration));

        // Register the fluid plugins.
        // We activate them later in the viewhelper.
        Registration::register(GeneralUtility::makeInstance(FluidConfiguration::class));
        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        // Theoretically, this should be part of the fluid debugger plugin, but
        // activating it in the viewhelper is too late, for obvious reason.
        if (
            version_compare(static::getTypo3Version(), '8.5', '>=') &&
            empty($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::FLUID]
            [static::FLUID_NAMESPACE][static::KREXX])
        ) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::FLUID]
            [static::FLUID_NAMESPACE][static::KREXX] = [ 0 => 'Brainworxx\\Includekrexx\\ViewHelpers'];
        }

        // Register the Aimeos Magic plugin.
        /** @var AimeosConfiguration $aimeosConfiguration */
        $aimeosConfiguration = GeneralUtility::makeInstance(AimeosConfiguration::class);
        Registration::register($aimeosConfiguration);

        // Check if we have the Aimeos shop available.
        if (class_exists(AimeosException::class) === true || ExtensionManagementUtility::isLoaded('aimeos')) {
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
     * @return $this
     *   Return $this, for chaining.
     */
    public function checkVersionNumber(string $version): Bootstrap
    {
        try {
            if ($version !== ExtensionManagementUtility::getExtensionVersion(static::EXT_KEY)) {
                GeneralUtility::makeInstance(CacheManager::class)
                    ->flushCachesInGroup('system');
            }
        } catch (Throwable $exception) {
            // Do nothing.
            // Flushing the cache just failed. There are deeper issues at work
            // here. The only thing to do now is trying not to brick the system.
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
    protected function loadKrexx(): bool
    {
        // There may be a composer version of kreXX installed.
        // We will not load the bundled one.
        if (defined('KREXX_DIR') === true) {
            return true;
        }

        // Simply load the main file.
        $krexxFile =  ExtensionManagementUtility::extPath(static::EXT_KEY) . 'Resources/Private/krexx/bootstrap.php';
        if (file_exists($krexxFile) === true && class_exists(Krexx::class, false) === false) {
            include_once $krexxFile;
            return true;
        }

        // Something went horribly wrong here.
        // Or . . .
        // More likely, the "autoloading" managed to bite us in the rear end.
        // No need to continue at this point.
        return false;
    }

    /**
     * Since the global constants TYPO3_version got himself deprecated, we must
     * use other means to get it.
     *
     * Wrapper around either
     *   - TYPO3_version
     *   - TYPO3\CMS\Core\Information\Typo3Version
     */
    protected function retrieveTypo3Version()
    {
        if (class_exists(Typo3Version::class)) {
            static::$typo3Version = GeneralUtility::makeInstance(Typo3Version::class)
                ->getVersion();
        } else {
            static::$typo3Version = TYPO3_version;
        }
    }

    /**
     * Getter for the TYPO3 version.
     *
     * @return string
     */
    public static function getTypo3Version(): string
    {
        return static::$typo3Version;
    }
}
