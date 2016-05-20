<?php
/**
 * @file
 *   Messages viewhelper substitute for the FlashMessagesViewHelper
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

use TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper;

// The mainproblem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put his thing here, hoping not to break anything!
if (!class_exists('Tx_Fluid_ViewHelpers_FlashMessagesViewHelper')) {
    /**
     * Class Tx_Fluid_ViewHelpers_FlashMessagesViewHelper
     */
    abstract class Tx_Fluid_ViewHelpers_FlashMessagesViewHelper extends FlashMessagesViewHelper
    {
    }
}
// For some reasons, Typo3 7.6 manages to load this file multiple times, causing
// a fatal.
if (class_exists('Tx_Includekrexx_ViewHelpers_MessagesViewHelper')) {
    return;
}

/**
 * The renderMode got removed in 8.0 from the FlashMessagesViewHelper.
 * But without it, it looks terrible in 4.5. We change this here with
 * our "own" viewhelper for Flash Messages in the backend.
 *
 * Class Tx_Includekrexx_ViewHelpers_MessagesViewHelper
 *
 * @usage
 *   {namespace krexx=Tx_Includekrexx_ViewHelpers}
 *   <krexx:messages />
 *
 * @see
 *   layout BackendSave.html
 */
class Tx_Includekrexx_ViewHelpers_MessagesViewHelper extends Tx_Fluid_ViewHelpers_FlashMessagesViewHelper
{

    /**
     * Short-circuited version of the render method, to make up for the missing
     * parameters in 4.5 and 4.7
     *
     * @param string $renderMode
     *   The render mode (div).
     * @return string
     *   The rendered message.
     * @throws \Tx_Fluid_Core_ViewHelper_Exception
     */
    public function render($renderMode = 'div')
    {
        return parent::render($renderMode);
    }

}
