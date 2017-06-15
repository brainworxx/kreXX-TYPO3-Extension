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

namespace Brainworxx\Krexx\View\Output;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Triggers the kreXX output during shutdown phase.
 *
 * @package Brainworxx\Krexx\View\Output
 */
class Shutdown extends AbstractOutput
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
     * Inject the pool and register the shutdown function.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        register_shutdown_function(array($this, 'shutdownCallback'));
    }

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
        // Output our chunks.
        // Every output is split into 4 chunk strings (header, messages,
        // data, footer).
        foreach ($this->chunkStrings as $chunkString) {
            // Send it to the browser.
            $this->pool->chunks->sendDechunkedToBrowser($chunkString);
        }
    }
}
