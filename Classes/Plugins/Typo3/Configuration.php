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

namespace Brainworxx\Includekrexx\Plugins\Typo3;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Configuration file for the TYPO3 kreXX plugin.
 *
 * Not to be confused with a TYPO3 frontend plugin.
 *
 * @package Brainworxx\Includekrexx\Plugins\Typo3
 */
class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getName()
    {
        return 'TYPO3 configuration';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getVersion()
    {
        return 'v1.0.0';
    }

    /**
     * TYPO3 specific stuff, like:
     *
     * - Register the overwrite for the configuration.
     * - Point the directories to the temp folder.
     * - Protect the temp folder, if necessary.
     */
    public static function exec()
    {
        // We are using the TYPO3 ip security, instead of the kreXX implementation.
        Registration::addRewrite(
            'Brainworxx\\Krexx\\Service\\Config\\Config',
            'Brainworxx\\Includekrexx\\Plugins\\Typo3\\Rewrites\\Config'
        );

        // See if we must create a temp directory for kreXX.
        $tempPaths = array(
            'main' => PATH_site . 'typo3temp/tx_includekrexx',
            'log' => PATH_site . 'typo3temp/tx_includekrexx/log',
            'chunks' => PATH_site . 'typo3temp/tx_includekrexx/chunks',
            'config' => PATH_site . 'typo3temp/tx_includekrexx/config',
        );

        // htAccess to prevent a listing
        $htAccess = 'order deny,allow' . chr(10) . 'deny from all';
        // Empty index.html in case the htaccess is not enough.
        $indexHtml = '';
        // Create and protect the temporal folders.
        foreach ($tempPaths as $key => $tempPath) {
            if (!is_dir($tempPath)) {
                // Create it!
                GeneralUtility::mkdir($tempPath);
                // Protect it!
                GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . '.htaccess', $htAccess);
                GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . 'index.html', $indexHtml);
            }
        }

        // Register it!
        Registration::setConfigFile($tempPaths['config'] . '/Krexx.ini');
        Registration::setChunksFolder($tempPaths['chunks'] . '/');
        Registration::setLogFolder($tempPaths['log'] . '/');

        // Adding our debugging balcklist.
        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        Registration::addMethodToDebugBlacklist(
            '\\TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper',
            '__toString'
        );

        // Deleting all rows from the DB via typo3 repository is NOT a good
        // debug method!
        Registration::addMethodToDebugBlacklist(
            '\\TYPO3\\CMS\\Extbase\\Persistence\\RepositoryInterface',
            'removeAll'
        );
        Registration::addMethodToDebugBlacklist(
            'Tx_Extbase_Persistence_RepositoryInterface',
            'removeAll'
        );

        // The lazy loading proxy may not have loaded the object at this time.
        Registration::addMethodToDebugBlacklist(
            '\\TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy',
            '__toString'
        );

        // Add additional texts to the help.
        $extPath = ExtensionManagementUtility::extPath(Bootstrap::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/t3.kreXX.ini');
    }
}
