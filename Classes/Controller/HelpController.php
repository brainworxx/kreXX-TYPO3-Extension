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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend help controller for the kreXX typo3 extension
 */
class HelpController extends CompatibilityController
{

    /**
     * Simply display the help text from the fluid template.
     */
    public function usageAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a write protected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addFlashMessage(
                $message,
                LocalizationUtility::translate('general.error.title', static::EXT_KEY),
                FlashMessage::ERROR
            );
        }

        $this->assignFlashInfo();
    }

    /**
     * Simply display the help text from the fluid template.
     */
    public function configAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a write protected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addFlashMessage(
                $message,
                LocalizationUtility::translate('general.error.title', static::EXT_KEY),
                FlashMessage::ERROR
            );
        }

        $this->view->assign(
            'pathToIni',
            $this->pool->fileService->filterFilePath($this->pool->config->getPathToIniFile())
        );

        $this->assignFlashInfo();
    }
}
