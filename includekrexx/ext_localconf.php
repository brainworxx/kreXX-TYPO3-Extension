<?php
/**
 * @file
 *   Installation check for kreXX
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

$filename = t3lib_extMgm::extPath($_EXTKEY, 'Resources/Private/krexx/Krexx.php');

if (file_exists($filename)) {
  include_once $filename;
}

// Sanity check to see if the path to the ini file is correct.
$filename = t3lib_extMgm::extPath('includekrexx') . 'Resources/Private/krexx/KrexxConfig.ini';
if (!is_file(\krexx\config::getPathToIni())) {
  // Huh, the config file does not exist, we need to go through the setup.
  // Looks like we need to delete the KrexxConfig.ini
  if (is_file($filename) && is_writable(dirname($filename))) {
    @unlink($filename);
  }
}

// We need to tell kreXX where to search for it's ini.
// Normally it would look inside the kreXX directory, but when you
// run an update, this file will be replaced.
if (! is_file($filename) && is_writeable(t3lib_extMgm::extPath('includekrexx'))) {
  $file_contents = '[pathtoini]' . PHP_EOL . 'pathtoini = "' . PATH_site . 'uploads/tx_includekrexx/Krexx.ini"' . PHP_EOL;
  // Write it to the kreXX Directory.
  file_put_contents($filename, $file_contents);
  // Make it writeable, so kreXX can be uninstalled manually.
  chmod($filename, 0777);

  // Protect the upload folder.
  // We create a .htaccess here, as well as a index.php.
  // The uploadfolder should not be reachable from the outside.
  $source = t3lib_extMgm::extPath('includekrexx') . 'Resources/Private/krexx/log/.htaccess';
  $destination = PATH_site . 'uploads/tx_includekrexx/.htaccess';
  if (is_file($source) && !is_file($destination)) {
    copy($source, $destination);
  }
  $source = t3lib_extMgm::extPath('includekrexx') . 'Resources/Private/krexx/log/index.php';
  $destination = PATH_site . 'uploads/tx_includekrexx/index.php';
  if (is_file($source) && !is_file($destination)) {
    copy($source, $destination);
  }
  // Finally, we create an empty config file here, so the dev can edit it later on.
  $file_contents = '; See Krexx.ini.example for configuration options.';
  $filename = PATH_site . 'uploads/tx_includekrexx/Krexx.ini';
  file_put_contents($filename, $file_contents);
  chmod($filename, 0777);
}

