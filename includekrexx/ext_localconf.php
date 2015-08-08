<?php
/**
 * @file
 *   Loader for the include kreXX extension
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

if (! defined('TYPO3_MODE')) {
  die('Access denied.');
}

if ((int)TYPO3_version < 7) {
  $filename = t3lib_extMgm::extPath($_EXTKEY, 'Resources/Private/krexx/Krexx.php');
}
else {
  $filename = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Resources/Private/krexx/Krexx.php');
}
if (file_exists($filename) && !class_exists('Krexx')) {
  // We load the kreXX library.
  // 7.3 is able to autoload krexx before this point.
  // We will not include it again!
  include_once $filename;
}
// We point kreXX to its ini file.
\Brainworxx\Krexx\Framework\Config::setPathToIni(PATH_site . 'uploads/tx_includekrexx/Krexx.ini');

// Typo3 7.4 does not autoload our controller anymore, so we do this here.
if (!class_exists('Tx_Includekrexx_Controller_IndexController') && (int)TYPO3_version > 6) {
  include_once (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Controller/IndexController.php'));
}