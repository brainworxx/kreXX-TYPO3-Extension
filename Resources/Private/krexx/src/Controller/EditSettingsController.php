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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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
 * "Controller" for the edit settings "action"
 *
 * @package Brainworxx\Krexx\Controller
 */
class EditSettingsController extends AbstractController
{
    /**
     * Outputs the edit settings dialog, without any analysis.
     *
     * @return $this
     *   Return $this for chaining
     */
    public function editSettingsAction()
    {
        if ($this->pool->emergencyHandler->checkMaxCall() === true) {
            // Called too often, we might get into trouble here!
            return $this;
        }

        $this->pool->reset();

        // We will not check this for the cookie config, to avoid people locking
        // themselves out.
        $this->pool->emergencyHandler->setDisable(true);

        // Find caller.
        $caller = $this->callerFinder->findCaller('Cookie Configuration', []);
        $this->pool->chunks->addMetadata($caller);

        // Render it.
        $footer = $this->outputFooter($caller, true);
        $this->pool->chunks->detectEncoding($footer);

        $this->outputService
            ->addChunkString($this->pool->render->renderHeader('Edit local settings', $this->outputCssAndJs()))
            ->addChunkString($footer);
        $this->pool->emergencyHandler->setDisable(false);
        $this->outputService->finalize();

        return $this;
    }
}
