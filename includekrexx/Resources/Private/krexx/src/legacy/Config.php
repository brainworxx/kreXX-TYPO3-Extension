<?php
/**
 * @file
 * Legacy class for kreXX
 * kreXX: Krumo eXXtended
 *
 * This is a debugging tool, which displays structured information
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

namespace Krexx;

/**
 * Legacy class for the configuration, thanks to the inability of the Typo3
 * extension manager to clear the ext_localconf cache.
 *
 * @package Krexx
 */
class Config {

  public static function getPathToIni() {
    if (class_exists('Brainworxx\Krexx\Framework\Config', false)) {
      return \Brainworxx\Krexx\Framework\Config::getPathToIni();

    }
    else {
      // Something went very wrong here, offer some fallback!
      return __FILE__ . 'Krexx.ini';
    }
  }

}