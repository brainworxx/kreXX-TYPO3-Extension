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

// Register BE module.
if (TYPO3_MODE === 'BE') {

    $registered = false;
    // 6.0 ++
    if (class_exists('\\TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility')) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $_EXTKEY,
            'tools',
            'kreXX configuration',
            '',
            array(
                'Config' => 'edit,save',
                'FormConfig' => 'edit,save',
                'Log' => 'list,getContent,delete',
                'Help' => 'usage,config',
                'Cookie' => 'index',
            ),
            array(
                'access' => 'user,group',
                'icon' => 'EXT:includekrexx/Resources/Public/Icons/icon_medium.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml',
            )
        );
        $registered = true;
    }

    // The old way.
    if (class_exists('Tx_Extbase_Utility_Extension') && !$registered) {
        Tx_Extbase_Utility_Extension::registerModule(// Main area.
            $_EXTKEY,
            'tools',
            'kreXX configuration',
            '',
            array(
                'Config' => 'edit,save',
                'FormConfig' => 'edit,save',
                'Log' => 'list,getContent,delete',
                'Help' => 'usage,config',
                'Cookie' => 'index',
            ),
            array(
                'access' => 'user,group',
                'icon' => 'EXT:includekrexx/ext_icon.gif',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml'
            )
        );
    }

}
