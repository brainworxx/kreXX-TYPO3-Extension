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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

/**
 * @deprecated
 *   Will be removed as soon as we drop TYPO3 11 support.
 */
if (!defined('TYPO3_MODE') && !defined('TYPO3')) {
    die('Access denied.');
}

// Register BE module.
call_user_func(
    function () {
        $typo3Version = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            TYPO3\CMS\Core\Information\Typo3Version::class
        )->getVersion();

        if (version_compare($typo3Version, '12.0.0', '<')) {
            // The old way of registering a backend module by a framework call.
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'Includekrexx',
                'tools',
                'kreXX configuration',
                '',
                [
                    \Brainworxx\Includekrexx\Controller\IndexController::class => 'index, save, dispatch'
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:includekrexx/Resources/Public/Icons/Extension.svg',
                    'labels' => 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf',
                ]
            );
        }
    }
);
