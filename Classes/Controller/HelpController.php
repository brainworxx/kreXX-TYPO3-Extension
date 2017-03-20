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

// The 7.3'er autoloader tries to include this file twice, probably
// because of the class mappings above. I need to make sure not to
// redeclare the Tx_Includekrexx_Controller_HelpController and throw
// a fatal.
if (class_exists('Tx_Includekrexx_Controller_HelpController')) {
    return;
}

/**
 * Backend help controller for the kreXX typo3 extension
 */
class Tx_Includekrexx_Controller_HelpController extends Tx_Includekrexx_Controller_CompatibilityController
{

    /**
     * Simply display the help text from the fluid template.
     */
    public function usageAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a writeprotected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
        }
        $this->addCssToView('Backend.css');
        $this->assignFlashInfo();
    }

    /**
     * Simply display the help text from the fluid template.
     */
    public function configAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a writeprotected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
        }
        $this->addCssToView('Backend.css');
        $this->assignFlashInfo();
    }
}
