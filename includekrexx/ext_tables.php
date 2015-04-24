<?php
/**
 * @file
 * Backend configuration for kreXX
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @license http://opensource.org/licenses/LGPL-2.1 GNU Lesser General Public License Version 2.1
 * @package Krexx
 */

if (!defined('TYPO3_MODE')) {
  die ('Access denied.');
}

// Register BE module.
if (TYPO3_MODE === 'BE') {
  if ((int)TYPO3_version < 7) {
    // Typo3 4.5+
    Tx_Extbase_Utility_Extension::registerModule(
      // Main area.
      $_EXTKEY, 'tools',
      // Name of the module.
      'kreXX configuration',
      // Position of the module.
      '',
      // Allowed controller action combinations.
      array('Index' => 'editConfig,editFeConfig,usageHelp,configHelp,saveConfig,saveFeConfig'),
      array(
        'access' => 'user,group',
        'icon' => 'EXT:includekrexx/ext_icon.gif',
        'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:be.krexx.modulname'
      )
    );
  }
  else {
    // Typo3 7+
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
      // Main area.
      $_EXTKEY, 'tools',
      // Name of the module.
      'kreXX configuration',
      // Position of the module.
      '',
      // Allowed controller action combinations.
      array('Index' => 'editConfig,editFeConfig,usageHelp,configHelp,saveConfig,saveFeConfig'),
      array(
        'access' => 'user,group',
        'icon' => 'EXT:includekrexx/ext_icon.gif',
        'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:be.krexx.modulname'
      )
    );
  }
}
