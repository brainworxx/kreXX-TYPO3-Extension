<?php
/**
 * @file
 *   Output string handling for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
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

namespace Krexx;


/**
 * Sends the kreXX output in the shutdown phase.
 *
 * @package Krexx
 */
class ShutdownHandler {

  /**
   * [0] -> The chunkedup string, that we intend to send to
   *        the browser.
   * [1] -> Are we ignoring local settings?
   *
   * @var array
   *   An array of all chunk strings.
   *   A chunk string are be:
   *   - header
   *   - messages
   *   - data part
   *   - footer
   *   This means, that every output is split in 4 parts
   */
  protected $chunkStrings = array();

  /**
   * Adds output to our shutdownhandler.
   *
   * @param string $chunk_string
   *   The chunked output string.
   * @param bool $ignore_local_settings
   *   Whether or not we ignore local settings.
   */
  public function addChunkString($chunk_string, $ignore_local_settings = FALSE) {
    $this->chunkStrings[] = array($chunk_string, $ignore_local_settings);
  }

  /**
   * The shutdown callback.
   *
   * It gets called when PHP is sutting down. It will render
   * out kreXX output, to guarantie minimal interference with
   * the hosting CMS.
   */
  public function shutdownCallback() {
    // Check for CLI and messages.
    if (php_sapi_name() == "cli") {
      $messages = Messages::outputMessages();
      // Since we are in CLI mode, these messages are not in HTML.
      // We can output them right away.
      echo $messages;
      // Something went wrong, better to stop right here.
      return;
    }

    // Output our chunks.
    // Every output is split into 4 chunk strings (header, messages,
    // data, footer).
    foreach ($this->chunkStrings as $chunk_string) {
      Toolbox::outputNow($chunk_string[0], $chunk_string[1]);
    }
  }
}
