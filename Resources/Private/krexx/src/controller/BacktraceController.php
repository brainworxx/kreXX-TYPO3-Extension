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

namespace Brainworxx\Krexx\Controller;

/**
 * "Controller" for the backtrace "action".
 *
 * @package Brainworxx\Krexx\Controller
 */
class BacktraceController extends AbstractController
{

    /**
     * Outputs a backtrace.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function backtraceAction()
    {
        if ($this->pool->emergencyHandler->checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return $this;
        }

        $this->pool->reset();
        // We overwrite the local settings, so we can get as much info from
        // analysed objects as possible.
        $this->pool->config->overwriteLocalSettings($this->configFatal);

        // Find caller.
        $caller = $this->callerFinder->findCaller();
        $caller['type'] = 'Backtrace';

        $this->pool->scope->setScope($caller['varname']);

        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();
        unset($backtrace[0]);

        $footer = $this->outputFooter($caller);
        $analysis = $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessBacktrace')
            ->process($backtrace, -1);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return $this;
        }

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        $this->pool->chunks->addMetadata($caller);

        $this->outputService->addChunkString($this->outputHeader('Backtrace'));
        $this->outputService->addChunkString($analysis);
        $this->outputService->addChunkString($footer);

        // Reset our configuration for the other analysis calls.
        $this->pool->resetConfig();

        return $this;
    }
}
