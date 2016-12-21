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

// The 7.3'er autoloader tries to include this file twice, probably
// because of the class mappings above. I need to make sure not to
// redeclare the Tx_Includekrexx_Controller_HelpController and throw
// a fatal.
if (!class_exists('Tx_Includekrexx_Controller_CookieController')) {

    /**
     * Cookie controller for the kreXX typo3 extension
     */
    class Tx_Includekrexx_Controller_CookieController extends Tx_Includekrexx_Controller_CompatibilityController
    {

        /**
         * Simply display the kreXX local browser configuration.
         */
        public function indexAction()
        {
            $this->addNamespace();
            
            // Has kreXX something to say? Maybe a writeprotected logfolder?
            foreach ($this->getTranslatedMessages() as $message) {
                $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
            }

            if ($this->krexxStorage->config->getSetting('disabled')) {
                // kreXX will not display anything, if it was disabled via:
                // - krexx::disable();
                // - Disable output --> true in the "Edit configuration file menu
                // We need to tell the user that krexx was disabled.
                $this->view->assign('is_disabled', true);
            } else {
                $this->view->assign('is_disabled', false);
            }

            if ($this->krexxStorage->config->getSetting('destination') == 'file') {
                // A file output will also prevent the options from popping ou here.
                // We need to tell the user that there is nothing to see here.
                $this->view->assign('is_file', true);
            } else {
                // Normal frontend output mode.
                $this->view->assign('is_file', false);
            }
            $this->addCssToView('Backend.css');
            \Krexx::editSettings();
        }
    }
}
