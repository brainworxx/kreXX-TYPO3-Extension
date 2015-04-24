<?php
/**
 * @file
 * Installation check for kreXX
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

