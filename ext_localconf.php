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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$boot = function ($_EXTKEY) {
    $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

    // We load the kreXX library.
    // The class_exists triggers the composer autoloading, if available.
    // It not, we use the bundled version wich comes with the externsion.
    $krexxFile = $extPath . 'Resources/Private/krexx/Krexx.php';
    if (file_exists($krexxFile) && !class_exists('Krexx')) {
        include_once $krexxFile;
    }

    // There is a bug with the extension installing (at least in TYPO3 8.7.8),
    // causing this class not being available, right after a manual upgrade.
    // It's not a showstopper, because after a reload, everything is OK.
    // We need to make sure that we have access to the plugin registration, to
    // prevent this ugly TYPO3 error message.
    if (!class_exists('\\Brainworxx\\Krexx\\Service\\Plugin\\Registration')) {
        return;
    }

    // Register and activate the TYPO3 plugin.
    \Brainworxx\Krexx\Service\Plugin\SettingsGetter::register(
        'Brainworxx\\Includekrexx\\Plugins\\Typo3\\Configuration'
    );
    \Brainworxx\Krexx\Service\Plugin\SettingsGetter::activatePlugin(
        'Brainworxx\\Includekrexx\\Plugins\\Typo3\\Configuration'
    );

    // Register the fluid plugins.
    // We activate them later in the viewhelper.
    \Brainworxx\Krexx\Service\Plugin\SettingsGetter::register(
        'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Configuration'
    );
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dataviewer')) {
        \Brainworxx\Krexx\Service\Plugin\SettingsGetter::register(
            'Brainworxx\\Includekrexx\\Plugins\\FluidDataViewer\\Configuration'
        );
    }

    if (version_compare(TYPO3_version, '8.5', '>=')) {
        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'] = array(
                0 => 'Brainworxx\\Includekrexx\\ViewHelpers'
            );
        }
    }
    // Add the legacy debug viewhelper, in case people are using the old krexx
    // namespace.
    if (!class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
        include_once $extPath . 'Classes/ViewHelpers/LegacyDebugViewHelper.php';
    }

    // Check if we have thze Aimeos shop available.
    // We can not rely on the extention manager to know about the shop, in case
    // it is required via composer.
    if (class_exists('Aimeos\\MShop\\Factory') === false) {
        // Register the Aimoes Magic plugin.
        \Brainworxx\Krexx\Service\Plugin\SettingsGetter::register(
            'Brainworxx\Includekrexx\Plugins\AimeosMagic\\Configuration'
        );
        \Brainworxx\Krexx\Service\Plugin\SettingsGetter::activatePlugin(
            'Brainworxx\Includekrexx\Plugins\AimeosMagic\\Configuration'
        );
    }

};

$boot($_EXTKEY);
unset($boot);
