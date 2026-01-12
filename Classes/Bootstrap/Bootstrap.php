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

namespace Brainworxx\Includekrexx\Bootstrap;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration as AimeosConfiguration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Includekrexx\Plugins\ContentBlocks\Configuration as ContentBlocksConfiguration;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Batch for the bootstrapping.
     */
    public function run(): void
    {
        if (!$this->loadKrexx()) {
            // "Autoloading" failed.
            // There is no point in continuing here.
            return;
        }

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
        $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::FLUID]
            [static::FLUID_NAMESPACE][static::KREXX][] = 'Brainworxx\\Includekrexx\\ViewHelpers';

        // Register the Aimeos Magic plugin.
        /** @var AimeosConfiguration $aimeosConfiguration */
        $aimeosConfiguration = GeneralUtility::makeInstance(AimeosConfiguration::class);
        Registration::register($aimeosConfiguration);

        // Check if we have the Aimeos shop available.
        if (ExtensionManagementUtility::isLoaded('aimeos')) {
            Registration::activatePlugin(get_class($aimeosConfiguration));
        }
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
        if (defined('KREXX_DIR')) {
            return true;
        }

        // Simply load the main file.
        $krexxFile =  ExtensionManagementUtility::extPath(static::EXT_KEY) . 'Resources/Private/krexx/bootstrap.php';
        if (file_exists($krexxFile)) {
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
