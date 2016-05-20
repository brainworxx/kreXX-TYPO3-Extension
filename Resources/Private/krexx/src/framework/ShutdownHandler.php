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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Framework;

use Brainworxx\Krexx\View\Messages;

/**
 * Sends the kreXX output in the shutdown phase.
 *
 * @package Brainworxx\Krexx\Framework
 */
class ShutdownHandler
{

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
     * Adds output to our shutdown handler.
     *
     * @param string $chunkString
     *   The chunked output string.
     */
    public function addChunkString($chunkString)
    {
        $this->chunkStrings[] = $chunkString;
    }

    /**
     * The shutdown callback.
     *
     * It gets called when PHP is shutting down. It will render
     * out kreXX output, to guarantee minimal interference with
     * the hosting CMS.
     */
    public function shutdownCallback()
    {
        // Check for CLI and messages.
        if (php_sapi_name() == "cli") {
            $messages = Messages::outputMessages();
            // Since we are in CLI mode, these messages are not in HTML.
            // We can output them right away.
            echo $messages;
        }

        // Output our chunks.
        // Every output is split into 4 chunk strings (header, messages,
        // data, footer).
        foreach ($this->chunkStrings as $chunkString) {
            if (Config::getConfigValue('output', 'destination') == 'file') {
                // Save it to a file.
                Chunks::saveDechunkedToFile($chunkString);
            } else {
                // Send it to the browser.
                Chunks::sendDechunkedToBrowser($chunkString);
            }
        }
    }
}
