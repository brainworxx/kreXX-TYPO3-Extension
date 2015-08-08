<?php
/**
 * @file
 *   Typo3 installation class for kreXX
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


class ext_update {

  /**
   * Returns wether this script is available in the backend.
   *
   * @return bool
   *   Always TRUE.
   */
  public function access() {
    return TRUE;
  }

  /**
   * Main Function.
   *
   * @return string
   *   Gives feedback of what was actually done.
   */
  public function main() {
    // Protect the upload folder.
    // We create a .htaccess here, as well as a index.php.
    // The uploadfolder should not be reachable from the outside.
    if ((int) TYPO3_version < 7) {
      $source = t3lib_extMgm::extPath('includekrexx') . 'Resources/Private/krexx/log/.htaccess';
    }
    else {
      $source =  TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('includekrexx') . 'Resources/Private/krexx/log/.htaccess';
    }
    $destination = PATH_site . 'uploads/tx_includekrexx/.htaccess';
    if (is_file($source) && !is_file($destination)) {
      copy($source, $destination);
    }
    if ((int) TYPO3_version < 7) {
      $source = t3lib_extMgm::extPath('includekrexx') . 'Resources/Private/krexx/log/index.php';
    }
    else {
      $source =  TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('includekrexx') . 'Resources/Private/krexx/log/.htaccess';
    }
    $destination = PATH_site . 'uploads/tx_includekrexx/index.php';
    if (is_file($source) && !is_file($destination)) {
      copy($source, $destination);
    }

    return 'Applied protection to the upload folder.';
  }

}