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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\View\Output;

/**
 * Triggers the kreXX output during shutdown phase.
 */
class Browser extends AbstractOutput
{
    /**
     * The shutdown callback.
     *
     * It gets called when PHP is shutting down. It will render
     * out kreXX output, to guarantee minimal interference with
     * the hosting CMS.
     */
    public function shutdownCallback(): void
    {
        // Output our chunks.
        // Every output is split into 4 chunk strings (header, messages,
        // data, footer).
        foreach ($this->chunkStrings as $chunkString) {
            // Send it to the browser.
            $this->pool->chunks->sendDechunkedToBrowser($chunkString);
        }
        $this->destruct();
    }

    /**
     * Register this one in the php shutdown phase.
     */
    public function finalize(): void
    {
        register_shutdown_function([$this, 'shutdownCallback']);
    }
}
