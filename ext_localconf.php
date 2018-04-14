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

    if (version_compare(TYPO3_version, '8.5', '>=')) {
        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'] = array(
                0 => 'Brainworxx\\Includekrexx\\ViewHelpers'
            );
        }
    }

    // Add our specific overwrites.
    // There is a bug with the extension installing (at least in TYPO3 8.7.8),
    // causing this class not being available, right after a manual upgrade.
    // It's not a showstopper, because after a reload, everything is OK.
    // We need to make sure that we have access to the overwrite class, to
    // prevent this ugly TYPO3 error message.
    $overwritesFile = $extPath . 'Resources/Private/krexx/src/Service/Overwrites.php';
    if (file_exists($overwritesFile) && !class_exists('\\Brainworxx\\Krexx\\Service\\Overwrites')) {
        include_once $overwritesFile;
    }
    \Brainworxx\Krexx\Service\Overwrites::$classes['Brainworxx\\Krexx\\Service\\Config\\Config'] =
        'Brainworxx\\Includekrexx\\Rewrite\\Service\\Config\\Config';



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
    foreach ($tempPaths as $key => $tempPath) {
        if (!is_dir($tempPath)) {
            // Create it!
            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($tempPath);
            // Protect it!
            \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . '.htaccess', $htAccess);
            \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . 'index.html', $indexHtml);
        }
        // Register it!
        \Brainworxx\Krexx\Service\Overwrites::$directories[$key] = $tempPath;
    }
};

$boot($_EXTKEY);
unset($boot);
