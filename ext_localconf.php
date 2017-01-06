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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

$registered = false;
$krexxFile = 'sdfsdfs';
// 6.0 ++
if (class_exists('\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility')) {
    $krexxFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Resources/Private/krexx/Krexx.php');
    $registered = true;
}
// The old way.
if (class_exists('t3lib_extMgm') && !$registered) {
    $krexxFile = t3lib_extMgm::extPath($_EXTKEY, 'Resources/Private/krexx/Krexx.php');
}
if (file_exists($krexxFile) && !class_exists('Krexx')) {
    // We load the kreXX library.
    // 7.3+ is able to autoload krexx before this point.
    // We will not include it again!
    include_once $krexxFile;
}




// Do some autoloading stuff which may or may not be done by TYPO3 automatically.
if (version_compare(TYPO3_version, '7.2', '>')) {
    // TYPO3 7.3 / 7.4 does not autoload our classes anymore, so we do this here.
    if (!class_exists('Tx_Includekrexx_Controller_CompatibilityController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/CompatibilityController.php'));
    }
    if (!class_exists('Tx_Includekrexx_Controller_FormConfigController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/FormConfigController.php'));
    }
    if (!class_exists('Tx_Includekrexx_Controller_LogController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/LogController.php'));
    }
    if (!class_exists('Tx_Includekrexx_Controller_HelpController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/HelpController.php'));
    }
    if (!class_exists('Tx_Includekrexx_Controller_ConfigController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/ConfigController.php'));
    }
    if (!class_exists('Tx_Includekrexx_Controller_CookieController')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/CookieController.php'));
    }
    if (!class_exists('Tx_Includekrexx_ViewHelpers_MessagesViewHelper')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/ViewHelpers/MessagesViewHelper.php'));
    }
    if (!class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/ViewHelpers/DebugViewHelper.php'));
    }
    if (!class_exists('Tx_Includekrexx_Rewrite_ServiceConfigSecurity')) {
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Rewrite/ServiceConfigSecurity.php'));
    }

    if (version_compare(TYPO3_version, '8.0' ,'>=')) {
        // Some special compatibility stuff for 8.0 , Fluid and it's ViewHelpers.
        if (!class_exists('\\Tx_Includekrexx_ViewHelpers\\MessagesViewHelper')) {
            include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/ViewHelpers/MessagesViewHelper8.php'));
        }
        if (!class_exists('\\Tx_Includekrexx_ViewHelpers\\DebugViewHelper')) {
            include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/ViewHelpers/DebugViewHelper8.php'));
        }
    }
}

// Register our overwrite in the kreXX lib.
// We have some special handling for the ip whitelisting in here.
\krexx::$pool->addRewrite('Brainworxx\\Krexx\\Service\\Config\\Security', 'Tx_Includekrexx_Rewrite_ServiceConfigSecurity');
// That class is located in the configuration, we need to relaod it.  :-(
// At least for now.
\krexx::$pool->resetConfig();