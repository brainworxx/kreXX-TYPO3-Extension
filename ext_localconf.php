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

$boot = function ($_EXTKEY) {
    if (class_exists('\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility')) {
        // 6.0 ++
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
    } else {
        // The old way.
        $extPath = t3lib_extMgm::extPath($_EXTKEY);
    }


    // Do some "autoloading" stuff which may or may not be done by TYPO3
    // automatically, depending on the version.
    if (version_compare(TYPO3_version, '7.2', '>')) {
        // TYPO3 7.3 / 7.4 does not autoload our classes anymore, so we do this here.
        if (!class_exists('Tx_Includekrexx_Controller_CompatibilityController')) {
            include_once($extPath . 'Classes/Controller/CompatibilityController.php');
        }
        if (!class_exists('Tx_Includekrexx_Controller_FormConfigController')) {
            include_once($extPath . 'Classes/Controller/FormConfigController.php');
        }
        if (!class_exists('Tx_Includekrexx_Controller_LogController')) {
            include_once($extPath . 'Classes/Controller/LogController.php');
        }
        if (!class_exists('Tx_Includekrexx_Controller_HelpController')) {
            include_once($extPath . 'Classes/Controller/HelpController.php');
        }
        if (!class_exists('Tx_Includekrexx_Controller_ConfigController')) {
            include_once($extPath . 'Classes/Controller/ConfigController.php');
        }
        if (!class_exists('Tx_Includekrexx_Controller_CookieController')) {
            include_once($extPath . 'Classes/Controller/CookieController.php');
        }
        if (!class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
            include_once($extPath . 'Classes/ViewHelpers/DebugViewHelper.php');
        }

        if (version_compare(TYPO3_version, '8.0', '>=')) {
            // Some special compatibility stuff for 8.0, Fluid and it's ViewHelpers.
            if (!class_exists('\\Tx_Includekrexx_ViewHelpers\\DebugViewHelper')) {
                include_once($extPath . 'Classes/ViewHelpers/DebugViewHelper8.php');
            }
        }
        if (version_compare(TYPO3_version, '8.5', '>=')) {
            // Register our debug-viewhelper globally, so people don't have to
            // do it inside the template. 'krexx' as a namespace should be unique enough.
            if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'] = array(
                    0 => 'Tx_Includekrexx_ViewHelpers'
                );
            }
        }

    }


    // Add our specific overwrites.
    // When we include the kreXX mainfile, it gets bootstrapped.
    // But then it is already to late for these overwrites.
    $GLOBALS['kreXXoverwrites'] = array(
        'classes' => array(
            'Brainworxx\\Krexx\\Service\\Config\\Config' => 'Tx_Includekrexx_Rewrite_ServiceConfigConfig'
        ),
        'directories' => array(),
    );
    if (!class_exists('Brainworxx\\Krexx\\Service\\Config\\Fallback')) {
        include_once($extPath . 'Resources/Private/krexx/src/service/config/Fallback.php');
    }
    if (!class_exists('Brainworxx\\Krexx\\Service\\Config\\Config')) {
        include_once($extPath . 'Resources/Private/krexx/src/service/config/Config.php');
    }
    if (!class_exists('Tx_Includekrexx_Rewrite_ServiceConfigConfig')) {
        include_once($extPath . 'Classes/Rewrite/ServiceConfigConfig.php');
    }


    // See if we must create a temp directory for kreXX.
    $tempPaths = array(
        'main' => PATH_site . 'typo3temp/tx_includekrexx',
        'log' => PATH_site . 'typo3temp/tx_includekrexx/log',
        'chunks' => PATH_site . 'typo3temp/tx_includekrexx/chunks',
        'config' => PATH_site . 'typo3temp/tx_includekrexx/config',
    );
    // htAccess to prevent a listing
    $htAccess = 'order deny,allow' . chr(10) . 'deny from all';
    // Empty index.html in caqse the htacess is not enough.
    $indexHtml = '';
    // Create and protect the temporal folders.
    if (class_exists('TYPO3\\CMS\\Core\\Utility\\GeneralUtility')) {
        foreach ($tempPaths as $key => $tempPath) {
            if (!is_dir($tempPath)) {
                // Create it!
                \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($tempPath);
                // Protect it!
                \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . '.htaccess', $htAccess);
                \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . 'index.html', $indexHtml);
            }
            // Register it!
            $GLOBALS['kreXXoverwrites']['directories'][$key] = $tempPath;
        }
    } else {
        foreach ($tempPaths as $key => $tempPath) {
            if (!is_dir($tempPath)) {
                // Create it!
                t3lib_div::mkdir($tempPath);
                // Protect it!
                t3lib_div::writeFileToTypo3tempDir($tempPath . '/' . '.htaccess', $htAccess);
                t3lib_div::writeFileToTypo3tempDir($tempPath . '/' . 'index.html', $indexHtml);
            }
        }
    }
    // Register it!
    $GLOBALS['kreXXoverwrites']['directories']['config'] = PATH_site . 'typo3temp/tx_includekrexx/config/Krexx.ini';


    // We load the kreXX library.
    // The class__exists triggers the composer autoloading, if available.
    // It not, we use the bundled version wich comes with the externsion.
    $krexxFile = $extPath . 'Resources/Private/krexx/Krexx.php';
    if (file_exists($krexxFile) && !class_exists('Krexx')) {
        include_once $krexxFile;
    }

};

$boot($_EXTKEY);
unset($boot);
